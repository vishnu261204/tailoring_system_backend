#!/bin/sh
set -eu

APP_DIR="/var/www/html"
ENV_FILE="${APP_ENV_FILE:-${APP_DIR}/.env}"
ENV_EXAMPLE_FILE="${APP_DIR}/.env.example"
PORT="${PORT:-8080}"

ensure_env_file() {
    if [ -f "${ENV_FILE}" ]; then
        return
    fi

    if [ ! -f "${ENV_EXAMPLE_FILE}" ]; then
        echo "Missing ${ENV_FILE} and ${ENV_EXAMPLE_FILE}; cannot initialize Laravel environment." >&2
        exit 1
    fi

    cp "${ENV_EXAMPLE_FILE}" "${ENV_FILE}"
}

env_file_has_app_key() {
    if [ ! -f "${ENV_FILE}" ]; then
        return 1
    fi

    grep -Eq '^[[:space:]]*APP_KEY=.+$' "${ENV_FILE}"
}

ensure_app_key() {
    if [ -n "${APP_KEY:-}" ] || env_file_has_app_key; then
        return
    fi

    php artisan key:generate --force --ansi --no-interaction
}

prepare_writable_directories() {
    mkdir -p \
        storage/framework/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/app/public \
        storage/logs \
        bootstrap/cache

    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwx storage bootstrap/cache
}

cache_laravel() {
    rm -f bootstrap/cache/*.php

    php artisan package:discover --ansi --no-interaction
    php artisan config:clear --ansi --no-interaction
    php artisan route:clear --ansi --no-interaction
    php artisan view:clear --ansi --no-interaction
    php artisan config:cache --ansi --no-interaction
    php artisan route:cache --ansi --no-interaction

    if [ -d resources/views ]; then
        php artisan view:cache --ansi --no-interaction
    fi
}

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed "s/__PORT__/${PORT}/g" /etc/apache2/sites-available/000-default.conf.template > /etc/apache2/sites-available/000-default.conf

ensure_env_file
prepare_writable_directories
ensure_app_key
cache_laravel

exec "$@"
