FROM php:8.2-fpm

# Definir variáveis de ambiente para otimização
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV NODE_ENV=production
ENV DEBIAN_FRONTEND=noninteractive

# Instalar dependências do sistema em uma única camada otimizada
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    ca-certificates \
    gnupg \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends caddy \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Instalar Node.js 20 LTS (mais rápido que o padrão do apt)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP em paralelo quando possível
RUN docker-php-ext-install -j$(nproc) pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar apenas package.json e package-lock.json primeiro para cache NPM
COPY package*.json ./

# Instalar dependências Node.js primeiro (incluindo dev para build)
RUN npm ci --no-audit --no-fund

# Copiar composer files para cache das dependências PHP
COPY composer.json composer.lock ./

# Instalar dependências PHP com otimizações
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copiar arquivos de configuração primeiro
COPY Caddyfile /etc/caddy/Caddyfile
COPY health-check.sh /usr/local/bin/health-check.sh
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Tornar scripts executáveis
RUN chmod +x /usr/local/bin/health-check.sh /usr/local/bin/entrypoint.sh

# Copiar código fonte (isso invalidará cache apenas quando código mudar)
COPY . .

# Build dos assets com otimizações para produção
RUN NODE_ENV=production npm run build && npm cache clean --force

# Executar scripts do composer após copiar todos os arquivos
RUN composer run post-autoload-dump

# Otimizar para produção
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# Configurar permissões e criar diretórios necessários
RUN mkdir -p public storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000
CMD ["/usr/local/bin/entrypoint.sh"]
