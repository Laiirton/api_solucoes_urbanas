#!/bin/bash

echo "🔄 Testando build de produção localmente..."

# Limpar caches
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear 
php artisan view:clear

# Reinstalar dependências
echo "📦 Reinstalando dependências do Node.js..."
rm -rf node_modules package-lock.json
npm install

# Build de produção
echo "🏗️ Executando build de produção..."
NODE_ENV=production npm run build

# Verificar se os arquivos foram gerados
echo "🔍 Verificando arquivos gerados..."
if [ -d "public/build" ]; then
    echo "✅ Pasta public/build criada com sucesso!"
    ls -la public/build/
else
    echo "❌ Erro: Pasta public/build não foi criada!"
    exit 1
fi

# Cache do Laravel
echo "💾 Criando caches do Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Build de produção concluído com sucesso!"
echo "🚀 Pronto para deploy!"