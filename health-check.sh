#!/bin/bash

# Script de health check para a aplicação Laravel
# Este script verifica se a aplicação está pronta para receber requisições

# Verificar se o arquivo de inicialização existe
if [ ! -f "/var/www/artisan" ]; then
  echo "Arquivo artisan não encontrado"
  exit 1
fi

# Verificar se o PHP está funcionando
if ! php -v > /dev/null 2>&1; then
  echo "PHP não está funcionando corretamente"
  exit 1
fi

# Verificar se as dependências estão instaladas
if [ ! -d "/var/www/vendor" ]; then
  echo "Dependências não instaladas"
  exit 1
fi

# Verificar se o diretório de armazenamento existe e tem permissões corretas
if [ ! -d "/var/www/storage" ]; then
  echo "Diretório de armazenamento não encontrado"
  exit 1
fi

echo "Aplicação pronta para receber requisições"
exit 0