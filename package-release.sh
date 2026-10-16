#!/usr/bin/env bash
set -euo pipefail
cd -- "$(dirname -- "$0")"

node --test tests/idea-wizard.test.mjs
composer audit --locked --no-dev
npm ci --ignore-scripts
npm audit --omit=dev --audit-level=low
npm run build

release_directory="$(pwd)/storage/app/releases"
mkdir -p -- "$release_directory"
chmod 700 -- "$release_directory"
release_file="$release_directory/ideias-$(date -u +%Y%m%dT%H%M%SZ).tar.gz"
umask 077
tar --exclude='bootstrap/cache/*.php' --exclude='public/hot' --exclude='public/storage' \
    -czf "$release_file" \
    app bootstrap config database/migrations database/seeders database/factories public resources routes artisan composer.json composer.lock \
    .env.production.example nginx.production.conf README.md
sha256sum -- "$release_file"
