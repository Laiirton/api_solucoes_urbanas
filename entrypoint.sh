#!/usr/bin/env sh
set -e

# Executar migrações
php artisan migrate --force || true

# Limpar caches primeiro
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Recriar caches otimizados
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Garantir que os links simbólicos estejam corretos
php artisan storage:link || true

# Verificar se os assets existem
ls -la public/build/ || echo "Build assets not found"

# Iniciar serviços
php-fpm &
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
