#!/bin/bash
# Run on the VPS from /var/www/caa-test/desk after pulling new code.
set -e
export PATH="$PWD/node_modules/.bin:$PATH"
npm ci
node scripts/with-app-env.mjs vite build
# migrate.mjs silently skips when DATABASE_URL is unset, so load .env for it.
node --env-file=.env scripts/migrate.mjs
sudo systemctl restart caa-desk
