# CAA Desk — VPS setup (addendum to vps-migration-plan-v2)

Desk lives at `/var/www/caa-test/desk`, own database `caa_desk`, subdomain `desk.caatest.tech`, port 3100 (localhost only).

## 1. DNS
- Add an A record: `desk.caatest.tech` → VPS IP (same as the other subdomains).

## 2. Database (as postgres)
```
sudo -u postgres psql
CREATE USER caa_desk_user WITH PASSWORD 'CHANGE_ME';
CREATE DATABASE caa_desk OWNER caa_desk_user;
\q
```

## 3. Code
The Desk is in the repo under `desk/` (branch `caa-desk` — merge it to `main` when you're happy, then the VPS just tracks `main`).
```
cd /var/www/caa-test
git fetch origin
git checkout caa-desk && git pull      # after merging: git checkout main && git pull
cd desk
cp deploy/env.production.example .env
nano .env        # fill DATABASE_URL password, BETTER_AUTH_SECRET (openssl rand -hex 32)
chmod 600 .env
./deploy/update.sh   # installs, builds, migrates (first run: restart will fail until step 4 — fine)
```

## 4. Service
```
sudo cp deploy/caa-desk.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now caa-desk
sudo systemctl status caa-desk
curl -I http://127.0.0.1:3100/      # expect 200
```

## 5. Nginx + HTTPS
```
sudo cp deploy/nginx-desk.conf /etc/nginx/sites-available/desk
sudo ln -s /etc/nginx/sites-available/desk /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d desk.caatest.tech
```
HTTPS is required — sign-in cookies are Secure-only and phones won't install the app without it.

## 6. The engine must be reachable
`COMPLIANCE_ENGINE_URL=http://127.0.0.1:8080` assumes the Compliance Engine runs on this VPS on port 8080 (per the v2 plan). If it's still only on the desktop, staff cannot sign in — the Desk has no passwords of its own.

## 7. Check
- Open https://desk.caatest.tech → sign in with a Chart Recorder login.
- `journalctl -u caa-desk -f` for `[engine-login]` errors.

## Updating later
`cd /var/www/caa-test && git pull && cd desk && ./deploy/update.sh`

## Notes
- Backups: add `caa_desk` to the existing pg_dump job.
- A staffer disabled in the engine keeps their Desk session up to ~7 days; remove them from `CAA_DESK_ALLOWED_USERS` and restart to cut off immediately (only if the allowlist is in use).
