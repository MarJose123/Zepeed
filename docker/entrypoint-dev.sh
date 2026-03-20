#!/usr/bin/env sh
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ ! -f database/database.sqlite ] && [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  mkdir -p database
  touch database/database.sqlite
fi

if [ ! -d vendor ]; then
  composer install --no-interaction
fi

if [ ! -d node_modules ]; then
  bun install
  bun run build
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
