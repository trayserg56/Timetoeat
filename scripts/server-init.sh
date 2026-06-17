#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/timetoeat"
REPO_URL="${REPO_URL:-https://github.com/trayserg56/Timetoeat.git}"

echo "==> Install Docker if missing"
if ! command -v docker >/dev/null 2>&1; then
  curl -fsSL https://get.docker.com | sh
  systemctl enable docker
  systemctl start docker
fi

echo "==> Clone or update repository"
mkdir -p /var/www
if [ ! -d "$APP_DIR/.git" ]; then
  git clone "$REPO_URL" "$APP_DIR"
else
  cd "$APP_DIR"
  git fetch origin main
  git reset --hard origin/main
fi

cd "$APP_DIR"

if [ ! -f .env ]; then
  cp .env.production.example .env
  echo "==> Created .env from .env.production.example — update secrets before first deploy"
fi

chmod +x scripts/deploy.sh scripts/server-init.sh

echo "==> Server init finished. Next: edit $APP_DIR/.env, then run scripts/deploy.sh"
