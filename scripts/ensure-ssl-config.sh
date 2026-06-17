#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

APP_URL="$(grep '^APP_URL=' .env 2>/dev/null | cut -d= -f2- | tr -d '\"' || true)"
DOMAIN="${APP_URL#https://}"
DOMAIN="${DOMAIN#http://}"
DOMAIN="${DOMAIN%%/*}"

if [[ -z "$DOMAIN" || ! -f "certbot/conf/live/${DOMAIN}/fullchain.pem" ]]; then
    exit 1
fi

echo "==> Restore nginx SSL config for ${DOMAIN}"
cp docker/nginx/ssl.conf docker/nginx/active-ssl.conf
sed -i "s/__DOMAIN__/${DOMAIN}/g" docker/nginx/active-ssl.conf

exit 0
