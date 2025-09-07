FROM php:8.4-fpm as base

ARG UID=1000
ARG GID=1000
ARG COMPOSER_ALLOW_SUPERUSER=1
ARG APP_ENV=prod

ENV APP_ENV=${APP_ENV}

WORKDIR /app

COPY .. ./

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
        libonig-dev libxml2-dev libicu-dev libpq-dev libxslt1-dev libssl-dev \
        libbz2-dev libsqlite3-dev libreadline-dev libtidy-dev libgmp-dev \
        zlib1g-dev libcurl4-openssl-dev pkg-config libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
        bcmath bz2 calendar exif ffi gd gettext intl opcache pcntl \
        pdo_pgsql soap sockets tidy xml zip

    # Install Composer
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

RUN groupadd -g ${GID} app \
 && useradd  -u ${UID} -g app -m -s /bin/bash app \
 && mkdir -p /app /var/log/php \
 && chown -R app:app /app /var/log/php

COPY .docker/php-fpm.conf /usr/local/etc/php-fpm.conf

ENV COMPOSER_HOME=/app/.composer

FROM base as php-prod

USER app

FROM base as php-dev

RUN pecl install xdebug && docker-php-ext-enable xdebug

USER app

RUN composer install

HEALTHCHECK --interval=30s --timeout=3s \
  CMD php-fpm -t || exit 1

CMD ["php-fpm"]
