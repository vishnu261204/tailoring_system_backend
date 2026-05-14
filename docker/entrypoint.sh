#!/bin/bash

set -e

cd /var/www/html

# Ensure .env exists
if [ ! -f .env ]; then
  cp .env.example .env
fi

# Generate key if missing
php artisan key:generate --force || true

# Clear caches (important for Railway)
php artisan config:clear || true
php artisan cache:clear || true

# Run migrations
php artisan migrate --force || true

# Cache for performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Permissions
chown -R www-data:www-data storage bootstrap/cache

exec "$@"