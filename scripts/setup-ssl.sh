#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

DOMAIN="${1:-}"
EMAIL="${2:-}"

if [[ -z "$DOMAIN" || -z "$EMAIL" ]]; then
  echo "Использование: bash scripts/setup-ssl.sh example.com admin@example.com"
  echo ""
  echo "Перед запуском:"
  echo "  1. Домен должен указывать A-записью на IP этого сервера."
  echo "  2. Порт 80 должен быть доступен снаружи."
  exit 1
fi

COMPOSE="docker compose -f compose.yaml -f compose.prod.yaml"
COMPOSE_SSL="docker compose -f compose.yaml -f compose.prod.yaml -f compose.ssl.yaml"

mkdir -p certbot/www certbot/conf certbot/work certbot/logs

if ! command -v certbot >/dev/null 2>&1; then
  echo "==> Install certbot"
  apt-get update
  apt-get install -y certbot
fi

echo "==> Request certificate for ${DOMAIN}"
certbot certonly \
  --webroot \
  -w "$ROOT_DIR/certbot/www" \
  --config-dir "$ROOT_DIR/certbot/conf" \
  --work-dir "$ROOT_DIR/certbot/work" \
  --logs-dir "$ROOT_DIR/certbot/logs" \
  -d "$DOMAIN" \
  --email "$EMAIL" \
  --agree-tos \
  --no-eff-email \
  --non-interactive

echo "==> Install nginx SSL config"
cp docker/nginx/ssl.conf docker/nginx/active-ssl.conf
sed -i "s/__DOMAIN__/${DOMAIN}/g" docker/nginx/active-ssl.conf

if grep -q '^APP_URL=' .env; then
  sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
else
  echo "APP_URL=https://${DOMAIN}" >> .env
fi

echo "==> Restart nginx with SSL"
$COMPOSE_SSL up -d nginx

echo "==> Clear config cache"
$COMPOSE exec -T app php artisan config:clear
$COMPOSE exec -T app php artisan config:cache

echo "==> Register Telegram webhook"
$COMPOSE exec -T app php artisan telegram:set-webhook "https://${DOMAIN}" || true

echo "==> Configure Telegram Mini App menu button"
$COMPOSE exec -T app php artisan telegram:set-web-app "https://${DOMAIN}" || true

echo "==> Done: https://${DOMAIN}"
