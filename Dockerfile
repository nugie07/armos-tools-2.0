FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    mysql-client \
    postgresql-dev \
    postgresql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        zip \
        gd \
        mbstring \
        intl \
        opcache \
        bcmath \
        pcntl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY . .

RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader 2>/dev/null || true; fi

EXPOSE 8000

ENTRYPOINT ["/bin/sh", "-c", "cd /var/www && exec sh docker-entrypoint.sh"]
