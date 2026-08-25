FROM php:8.4-fpm-alpine

RUN apk add --no-cache     bash     curl     git     icu-dev     libzip-dev     oniguruma-dev     postgresql-dev     unzip     && docker-php-ext-install     intl     mbstring     opcache     pdo_pgsql     zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend

COPY . .

RUN composer install     --no-dev     --prefer-dist     --no-interaction     --optimize-autoloader

COPY php-production.ini /usr/local/etc/php/conf.d/99-production.ini

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

CMD ["php-fpm", "-F"]
