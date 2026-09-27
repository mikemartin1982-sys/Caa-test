#!/bin/bash
# Deploys the latest main to the caatest.tech VPS.
#
#   caa-deploy          pull and rebuild only what changed
#   caa-deploy --all    rebuild portal and engine even if nothing changed
#
# Run as caaadmin (uses sudo for the engine service, backups and PHP-FPM).
# Order matters: new db/migrations SQL must be applied before the new
# engine starts, because Hibernate runs with ddl-auto=validate and refuses
# to boot against a schema that doesn't match its entities.
set -euo pipefail

REPO=/var/www/caa-test
PORTAL=$REPO/public-portal
ENGINE=$REPO/compliance-engine
JAR_SRC=$ENGINE/target/compliance-engine-0.1.0-SNAPSHOT.jar
JAR_DST=/opt/caa-engine/compliance-engine.jar
ENGINE_URL=http://127.0.0.1:8080/
export JAVA_HOME=${JAVA_HOME:-$(dirname "$(dirname "$(readlink -f "$(command -v javac)")")")}

FORCE_ALL=false
[[ "${1:-}" == "--all" ]] && FORCE_ALL=true

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
die()  { printf '\n\033[1;31mDEPLOY FAILED: %s\033[0m\n' "$*" >&2; exit 1; }

cd "$REPO"
# Ask for the sudo password up front and keep it fresh for the whole run,
# so a prompt can never appear mid-deploy while the portal is in
# maintenance mode.
sudo -v || die "sudo is required"
while kill -0 "$$" 2>/dev/null; do sudo -n true 2>/dev/null; sleep 50; done &

step "Checking for local edits on the server"
if [[ -n "$(git status --porcelain)" ]]; then
    git status --short
    die "the server copy has uncommitted changes; commit them from your PC instead, or discard them with: git -C $REPO checkout -- ."
fi

step "Backing up before deploying"
sudo /usr/local/sbin/caa-backup

step "Pulling from GitHub"
OLD=$(git rev-parse HEAD)
git pull --ff-only
NEW=$(git rev-parse HEAD)

if [[ "$OLD" == "$NEW" && "$FORCE_ALL" == false ]]; then
    echo "Already up to date ($(git log -1 --format='%h %s')). Use --all to rebuild anyway."
    exit 0
fi

CHANGED=$(git diff --name-only "$OLD" "$NEW")
[[ -n "$CHANGED" ]] && git log --oneline "$OLD..$NEW"

portal_changed=$FORCE_ALL
engine_changed=$FORCE_ALL
grep -q '^public-portal/'     <<<"$CHANGED" && portal_changed=true
grep -q '^compliance-engine/' <<<"$CHANGED" && engine_changed=true
NEW_SQL=$(git diff --name-only --diff-filter=A "$OLD" "$NEW" -- 'db/migrations/*.sql' || true)
CHANGED_SQL=$(git diff --name-only --diff-filter=M "$OLD" "$NEW" -- 'db/migrations/*.sql' || true)

if [[ -n "$CHANGED_SQL" ]]; then
    echo "Note: these existing migration files were edited (not re-run automatically):"
    echo "$CHANGED_SQL" | sed 's/^/    /'
fi

if [[ -n "$NEW_SQL" ]]; then
    step "New database migrations"
    echo "$NEW_SQL" | sed 's/^/    /'
    read -rp "Apply these to caa_platform now, in this order? [y/N] " answer
    if [[ "$answer" =~ ^[Yy]$ ]]; then
        for f in $NEW_SQL; do
            echo "--- $f"
            psql -h 127.0.0.1 -U caa_engine_user -d caa_platform -v ON_ERROR_STOP=1 -f "$REPO/$f" \
                || die "$f failed; the engine was NOT restarted. Fix the SQL, then rerun caa-deploy --all."
        done
    else
        die "migrations not applied; the engine was NOT restarted. Rerun when ready."
    fi
fi

if [[ "$portal_changed" == true ]]; then
    step "Updating the portal"
    cd "$PORTAL"
    php artisan down --retry=15 || true
    trap 'php artisan up >/dev/null 2>&1 || true' EXIT
    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    sudo systemctl reload php8.4-fpm
    php artisan up
    trap - EXIT
    cd "$REPO"
fi

if [[ "$engine_changed" == true ]]; then
    step "Building the engine"
    (cd "$ENGINE" && sh mvnw -q -DskipTests package) || die "engine build failed; the running engine was not touched"

    step "Restarting the engine"
    sudo cp "$JAR_DST" "$JAR_DST.prev"
    sudo install -m 644 "$JAR_SRC" "$JAR_DST"
    sudo systemctl restart caa-engine

    healthy=false
    for _ in $(seq 1 30); do
        code=$(curl -s -o /dev/null -w '%{http_code}' "$ENGINE_URL" || true)
        if [[ "$code" == "401" || "$code" == "200" ]]; then healthy=true; break; fi
        sleep 3
    done

    if [[ "$healthy" != true ]]; then
        echo "Engine did not come up; last log lines:"
        sudo journalctl -u caa-engine -n 25 --no-pager
        echo "Rolling back to the previous engine version..."
        sudo cp "$JAR_DST.prev" "$JAR_DST"
        sudo systemctl restart caa-engine
        die "new engine failed to start and was rolled back (the portal may already be on the new code)"
    fi
    echo "Engine is up."
fi

step "Deployed $(git log -1 --format='%h %s')"
