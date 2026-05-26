# Stage 1: установка Composer-зависимостей (как AS vendor в вашем moto-примере)
FROM composer:2 AS composer

WORKDIR /app

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        zip \
        intl \
        mbstring \
        bcmath \
    && apk del .build-deps

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# Stage 2: финальный образ PHP-FPM + код приложения
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache \
        pcntl \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

RUN { \
    echo '[www]'; \
    echo 'clear_env = no'; \
    } > /usr/local/etc/php-fpm.d/zz-clear-env.conf

WORKDIR /var/www/html

COPY --from=composer /app/vendor ./vendor
COPY . .

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
