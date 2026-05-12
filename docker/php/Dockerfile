FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && apk add --no-cache \
        bash git curl unzip linux-headers \
        icu-dev oniguruma-dev libzip-dev sqlite-dev \
        postgresql-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        ghostscript qpdf imagemagick poppler-utils \
        nodejs npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl mbstring pcntl pdo_pgsql pgsql pdo_sqlite bcmath zip gd opcache sockets exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Tuned opcache settings — Windows Docker bind-mount + Laravel boot is
# unworkably slow at PHP defaults. See docker/php/opcache.ini for rationale.
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
