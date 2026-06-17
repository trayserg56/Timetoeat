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

echo "==> Build frontend"
docker run --rm \
  -v "$ROOT_DIR:/var/www/html" \
  -w /var/www/html \
  node:24-alpine \
  sh -lc "npm ci && npm run build"

echo "==> Run migrations"
$COMPOSE run --rm app php artisan migrate --force --no-interaction

echo "==> Optimize Laravel"
$COMPOSE run --rm app php artisan storage:link --force || true
$COMPOSE run --rm app php artisan config:cache
$COMPOSE run --rm app php artisan route:cache
$COMPOSE run --rm app php artisan view:cache

echo "==> Restart application"
$COMPOSE up -d app nginx

APP_URL="$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '\"')"
if [[ "$APP_URL" == https://* ]]; then
  echo "==> Register Telegram webhook"
  $COMPOSE exec -T app php artisan telegram:set-webhook "$APP_URL" || echo "Telegram webhook skipped: configure bot token, chat id and secret in admin."
fi

echo "==> Deploy finished"
