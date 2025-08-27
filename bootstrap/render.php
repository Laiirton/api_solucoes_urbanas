<?php

// Script de inicialização para Render.com
if (getenv('APP_ENV') === 'production') {
    // Executar migrações automaticamente
    exec('php artisan migrate --force');
    
    // Limpar e otimizar caches
    exec('php artisan config:cache');
    exec('php artisan route:cache');
    exec('php artisan view:cache');
    
    // Criar link simbólico para storage (se necessário)
    if (!file_exists(public_path('storage'))) {
        exec('php artisan storage:link');
    }
}
