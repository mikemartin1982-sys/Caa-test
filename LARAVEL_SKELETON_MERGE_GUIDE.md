# Merging `public-portal` into a Real Laravel Install

## Why this guide exists instead of the missing files just being provided

`public-portal/` as delivered is the CAA-specific application code —
Controllers, the Compliance Engine API client, routes, and most of the
views. It is **not a runnable Laravel installation** on its own. A real
Laravel 11 app needs framework skeleton files this project never
generated: `artisan`, `public/index.php` (the actual entry point
Apache/XAMPP needs to point at), `bootstrap/app.php`, `config/app.php`
and its neighbors, and the whole `vendor/` directory from Composer.

I could have hand-typed these, the same way I hand-typed `mvnw.cmd` — but
that's exactly how the wrapper ended up with a line-continuation bug and
then a line-ending bug, both real mistakes that cost you two rounds of
debugging. Laravel's bootstrap internals are more complex and more
version-sensitive than a wrapper script, and I have no way to execute or
verify them here. Rather than risk a third round of that, the reliable
path is to let Composer generate a guaranteed-correct skeleton and merge
the custom code into it.

## Steps

**1. Generate a fresh Laravel 11 skeleton**, anywhere temporary (not
inside `caa-platform/`, and — learned from the Maven/OneDrive issue —
outside any cloud-synced folder):

```powershell
cd C:\dev
composer create-project laravel/laravel temp-laravel-skeleton
```

This needs Packagist reachable, which it will be on your normal network.

**2. Copy the custom application code into it**, preserving the
skeleton's own files and only adding/overwriting what's CAA-specific:

```powershell
$skeleton = "C:\dev\temp-laravel-skeleton"
$custom = "C:\dev\caa-platform\public-portal"

# App code -- entirely CAA-specific, safe to copy wholesale
Copy-Item "$custom\app\*" "$skeleton\app\" -Recurse -Force

# Routes -- the skeleton ships its own routes/web.php with a demo route;
# REPLACE it, don't merge, since ours fully replaces the default
Copy-Item "$custom\routes\web.php" "$skeleton\routes\web.php" -Force

# Views -- entirely CAA-specific
Copy-Item "$custom\resources\views\*" "$skeleton\resources\views\" -Recurse -Force

# The one migration we wrote (users table) -- the skeleton already has
# its own default users/cache/jobs migrations. DELETE the skeleton's
# default 0001_01_01_*_create_users_table.php equivalents first, then
# copy ours in, so you don't end up with two competing users tables.
Copy-Item "$custom\database\migrations\*" "$skeleton\database\migrations\" -Force

# Config -- ADD our services.php entry rather than overwrite the
# skeleton's config/services.php wholesale, since Laravel's default one
# has other entries (mail, aws, etc.) you'll likely want to keep.
# Open both files and manually merge the 'compliance_engine' block from
# ours into the skeleton's config/services.php.

# .env -- same idea: merge our COMPLIANCE_ENGINE_* lines into the
# skeleton's generated .env, don't overwrite it (it has APP_KEY etc.
# already generated correctly).
```

**3. Add the one extra dependency** (Guzzle, for the HTTP client):
```powershell
cd $skeleton
composer require guzzlehttp/guzzle
```

**4. Point Apache/XAMPP at `$skeleton\public\`** — not the project root.
Laravel's actual web-facing entry point is `public/index.php`; the rest
of the app directory should never be directly web-accessible.

**5. Run the migrations** against the Postgres/MySQL you've got running
locally (adjust `.env`'s `DB_CONNECTION` if you're using Postgres for
Laravel's own tables too, though the architecture doc's assumption was
MySQL for Laravel's own auth/session/cache tables, separate from the
Compliance Engine's Postgres):
```powershell
php artisan migrate
```

**6. Set `COMPLIANCE_ENGINE_BASE_URL`** in `.env` to wherever your Spring
Boot service actually ends up running (`http://localhost:8080/api/v1` if
it's local).

## What's still missing after this merge

- **Real authentication.** Both the Client Portal (`auth` middleware on
  `portal.*` routes) and the admin routes (also currently `auth`, which
  is a placeholder — see `routes/web.php`'s comment) assume a login
  system exists. Laravel Breeze (`composer require laravel/breeze --dev`
  then `php artisan breeze:install`) is the fastest path to a working
  login/register flow; you'd then want a separate guard for staff vs.
  client if you want the admin and portal sides to have genuinely
  different auth, rather than sharing one `users` table.
- **No security on the Java API at all** — this is worth flagging again
  even though it's in the compliance-engine README too. Every endpoint
  is wide open right now. Don't expose the Spring Boot service to
  anything but `localhost`/your local network until that's addressed.
