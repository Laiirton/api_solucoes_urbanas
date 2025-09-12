@echo off
echo 🔄 Testando build de produção localmente...

REM Limpar caches
echo 🧹 Limpando caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear 
php artisan view:clear

REM Reinstalar dependências
echo 📦 Reinstalando dependências do Node.js...
if exist node_modules rmdir /s /q node_modules
if exist package-lock.json del package-lock.json
npm install

REM Build de produção
echo 🏗️ Executando build de produção...
set NODE_ENV=production
npm run build

REM Verificar se os arquivos foram gerados
echo 🔍 Verificando arquivos gerados...
if exist public\build (
    echo ✅ Pasta public\build criada com sucesso!
    dir public\build
) else (
    echo ❌ Erro: Pasta public\build não foi criada!
    exit /b 1
)

REM Cache do Laravel
echo 💾 Criando caches do Laravel...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ✅ Build de produção concluído com sucesso!
echo 🚀 Pronto para deploy!