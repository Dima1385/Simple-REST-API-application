# syntax=docker/dockerfile:1.7

FROM php:8.5-fpm AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    APP_HOME=/var/www/app \
    DEBIAN_FRONTEND=noninteractive

# install-php-extensions transparently knows how to build extensions for any
# current PHP version (including 8.5, where stock docker-php-ext-install has
# upstream issues with the new extension API directory).
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        postgresql-client \
        unzip \
        zip \
    && install-php-extensions \
        intl \
        opcache \
        pdo_pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR ${APP_HOME}

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
