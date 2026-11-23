# syntax=docker/dockerfile:1
# Production image for Property Connect BD (ADR-0018). Targets: app (php-fpm / artisan) and web (nginx).

FROM php:8.3-fpm-alpine AS base
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql gd intl zip bcmath opcache \
    && apk add --no-cache su-exec
WORKDIR /var/www/html

FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

FROM base AS app
COPY deploy/php.ini /usr/local/etc/php/conf.d/zz-pcbd.ini
COPY --from=vendor /var/www/html ./
COPY --from=assets /app/public/build ./public/build
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -s ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
