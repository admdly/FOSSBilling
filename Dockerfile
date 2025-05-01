# Create a base image with PHP-FPM and required packages. 8.4-fpm-alpine
# Install required packages (icu-dev) and PHP extensions (intl, pdo_mysql).
FROM php:8.4-fpm-alpine AS base
RUN apk update \
    && apk add icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl \
    && docker-php-ext-configure pdo_mysql \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && apk cache purge

# Extend the base image with recommended packages and PHP extensions.
# Install recommended packages (bzip2-dev, libpng-dev, zlib-dev) and PHP extensions (bz2, gd, opcache).
FROM base AS base-recommended
RUN apk update \
    && apk add bzip2-dev libpng-dev zlib-dev \
    && docker-php-ext-configure bz2 \
    && docker-php-ext-install -j$(nproc) bz2 \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-configure opcache \
    && docker-php-ext-install -j$(nproc) opcache \
    && apk cache purge

# Create a final image with the base-recommended image.
FROM base-recommended AS final
COPY --chown=www-data:www-data ./src/. /var/www/html
