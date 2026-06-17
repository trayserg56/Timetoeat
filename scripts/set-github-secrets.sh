#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
KEY_FILE="$ROOT_DIR/.deploy/deploy_key"

if ! command -v gh >/dev/null 2>&1; then
  echo "GitHub CLI (gh) is required. Install: brew install gh && gh auth login"
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Run: gh auth login"
  exit 1
fi

if [ ! -f "$KEY_FILE" ]; then
  echo "Deploy key not found at $KEY_FILE"
  exit 1
fi

gh secret set SERVER_HOST -b "5.253.188.165"
gh secret set SERVER_USER -b "root"
gh secret set SERVER_SSH_KEY < "$KEY_FILE"

echo "GitHub Actions secrets configured."
