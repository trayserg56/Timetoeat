#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE="docker compose -f compose.yaml -f compose.prod.yaml"

echo "==> Pull latest code"
git fetch origin main
git reset --hard origin/main

echo "==> Build app image"
$COMPOSE build app

echo "==> Start infrastructure"
$COMPOSE up -d postgres redis

echo "==> Install PHP dependencies"
$COMPOSE run --rm app composer install --no-dev --optimize-autoloader --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
  echo "==> Generate APP_KEY"
  $COMPOSE run --rm app php artisan key:generate --force
fi

echo "==> Verify frontend build"
if [ ! -f public/build/manifest.json ]; then
  echo "ERROR: public/build/manifest.json not found."
  echo "Run npm run build locally and commit public/build before deploy."
  exit 1
fi

echo "==> Run migrations"
$COMPOSE run --rm app php artisan migrate --force --no-interaction

echo "==> Fix storage permissions"
$COMPOSE run --rm app sh -c 'mkdir -p storage/app/private/receipts storage/framework/{cache,sessions,views} storage/logs && chown -R www-data:www-data storage bootstrap/cache && chmod -R ug+rwx storage bootstrap/cache'

echo "==> Optimize Laravel"
$COMPOSE run --rm app php artisan storage:link --force || true
$COMPOSE run --rm app php artisan config:cache
$COMPOSE run --rm app php artisan route:cache
$COMPOSE run --rm app php artisan view:cache

echo "==> Restart application"
COMPOSE="docker compose -f compose.yaml -f compose.prod.yaml"
if bash scripts/ensure-ssl-config.sh; then
  COMPOSE="$COMPOSE -f compose.ssl.yaml"
fi
$COMPOSE up -d app nginx

APP_URL="$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '\"')"
if [[ "$APP_URL" == https://* ]]; then
  echo "==> Register Telegram webhook"
  $COMPOSE exec -T app php artisan telegram:set-webhook "$APP_URL" || echo "Telegram webhook skipped: configure bot token, chat id and secret in admin."
  echo "==> Configure Telegram Mini App menu button"
  $COMPOSE exec -T app php artisan telegram:set-web-app "$APP_URL" || echo "Telegram Mini App skipped: check HTTPS URL and bot token."
fi

echo "==> Deploy finished"
