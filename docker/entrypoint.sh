#!/bin/sh
set -eu

PORT="${PORT:-8080}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf.template > /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set. Configure it in the container environment before starting the app." >&2
    exit 1
fi

php artisan package:discover --ansi
php artisan config:clear --ansi
php artisan route:clear --ansi
php artisan view:clear --ansi
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

exec "$@"
