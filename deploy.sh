#!/usr/bin/env bash
# Deploy script para reclamaciones.vldigitalagency.com.
# Se ejecuta en el server vía SSH desde el GitHub Actions workflow.
# Asume que: (a) el dir es un git checkout del repo, (b) .env y public/.user.ini
# ya existen como untracked.
set -euo pipefail

cd "$(dirname "$0")"

echo ">>> [1/5] git pull origin main"
git pull --ff-only origin main

echo ">>> [2/5] composer install --no-dev --optimize-autoloader"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo ">>> [3/5] php artisan migrate --force"
php artisan migrate --force

echo ">>> [4/5] cache: config + route + view"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ">>> [5/5] storage:link (idempotente)"
php artisan storage:link 2>/dev/null || true

echo "✓ Deploy OK — $(date '+%Y-%m-%d %H:%M:%S')"
