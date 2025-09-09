#!/usr/bin/env sh
set -e

php artisan migrate:fresh --force || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm &
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
