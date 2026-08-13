# syntax=docker/dockerfile:1

# ---- Stage 1: build frontend assets (Vite + Tailwind) ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
RUN npm run build

# ---- Stage 2: application ----
FROM dunglas/frankenphp:php8.3-alpine


RUN install-php-extensions pdo_pgsql gd zip intl bcmath pcntl opcache

RUN printf '%s\n' \
        'upload_max_filesize=64M' \
        'post_max_size=64M' \
        'memory_limit=512M' \
        'max_execution_time=300' \
        'opcache.enable=1' \
        'opcache.validate_timestamps=0' \
        'opcache.max_accelerated_files=20000' \
        'opcache.memory_consumption=192' \
    > /usr/local/etc/php/conf.d/app.ini

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
             storage/logs storage/app/public storage/app/private storage/app/data_log \
 && chown -R www-data:www-data storage bootstrap/cache

ENV SERVER_NAME=:80
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up >/dev/null 2>&1 || exit 1
