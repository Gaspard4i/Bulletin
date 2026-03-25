# =============================================================================
# Stage 1: Composer dependencies
# =============================================================================
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer config platform.php 8.3.30 && \
    composer update \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

# =============================================================================
# Stage 2: Development
# =============================================================================
FROM php:8.3-fpm-alpine AS dev

RUN apk add --no-cache \
        icu-dev \
        postgresql-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        opcache \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS linux-headers

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# In dev, we mount the source as a volume, but copy composer files for initial setup
COPY composer.json composer.lock ./
RUN composer update --prefer-dist --no-interaction --no-scripts

COPY . .

RUN composer dump-autoload

# Ensure var directory exists with correct permissions
RUN mkdir -p var/cache var/log && chmod -R 777 var

EXPOSE 9000

# Create var dirs on startup (needed when volume is mounted over /app)
CMD ["sh", "-c", "mkdir -p /app/var/cache /app/var/log && php-fpm"]

# =============================================================================
# Stage 3: Production
# =============================================================================
FROM php:8.3-fpm-alpine AS prod

RUN apk add --no-cache \
        icu-dev \
        postgresql-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        intl \
        opcache

COPY docker/php/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

# Copy only production dependencies from composer stage
COPY --from=composer /app/vendor ./vendor
COPY . .

# Warm up Symfony cache
RUN php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

# Ensure var directory exists with correct permissions
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
