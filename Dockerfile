FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libicu-dev \
        nginx supervisor gettext-base \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql zip gd mbstring xml bcmath intl opcache \
    && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Render turns any dashboard env var into a build ARG of the same name for
# Docker-based services, so setting INCLUDE_DEV_DEPS=true on a given
# service (e.g. a staging service that needs Faker for DemoDataSeeder,
# since fake() only exists when fakerphp/faker is actually installed) pulls
# dev dependencies into that image, while the default (unset) keeps
# production lean via --no-dev.
ARG INCLUDE_DEV_DEPS=false

COPY composer.json composer.lock ./
RUN if [ "$INCLUDE_DEV_DEPS" = "true" ]; then \
        composer install --no-scripts --no-autoloader --prefer-dist; \
    else \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN if [ "$INCLUDE_DEV_DEPS" = "true" ]; then \
        composer dump-autoload --optimize; \
    else \
        composer dump-autoload --optimize --no-dev; \
    fi \
    && npm run build \
    && npm prune --omit=dev \
    && rm -rf node_modules/.cache

RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 10000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data

ENTRYPOINT ["docker-entrypoint.sh"]
