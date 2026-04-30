#!/bin/sh
set -e

# Idempotent first-run bootstrap. Runs on every container start; each
# step is a no-op if already done.

if [ ! -d vendor ]; then
    echo "[entrypoint] installing composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f .env ]; then
    echo "[entrypoint] creating .env from .env.example..."
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    echo "[entrypoint] generating APP_KEY..."
    php artisan key:generate --force
fi

if [ ! -f database/database.sqlite ]; then
    echo "[entrypoint] creating sqlite database..."
    touch database/database.sqlite
fi

php artisan migrate --graceful --force
php artisan db:seed --force

exec "$@"
