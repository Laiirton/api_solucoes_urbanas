FROM php:8.2-fpm

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    nodejs \
    npm \
    ca-certificates \
    gnupg \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list \
    && apt-get update \
    && apt-get install -y caddy

# Instalar extensões PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar composer files para cache das dependências
COPY composer.json composer.lock ./

# Instalar dependências PHP (etapa separada para melhorar cache)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copiar arquivos do projeto
COPY . .

# Copiar script de health check
COPY health-check.sh /usr/local/bin/health-check.sh
RUN chmod +x /usr/local/bin/health-check.sh

# Copiar arquivo de configuração do Caddy
COPY Caddyfile /etc/caddy/Caddyfile

# Instalar dependências Node.js e build assets
RUN npm ci && npm run build

# Definir variável de ambiente para permitir execução do Composer como root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Executar scripts do composer após copiar todos os arquivos
RUN composer run post-autoload-dump

RUN php artisan key:generate --force || true

# Configurar permissões
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Criar diretório público para os assets
RUN mkdir -p public

EXPOSE 8000
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
CMD ["/usr/local/bin/entrypoint.sh"]
