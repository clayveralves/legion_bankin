FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite intl zip \
    && git config --global --add safe.directory /var/www/html \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

CMD ["php-fpm"]