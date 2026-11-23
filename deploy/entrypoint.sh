#!/bin/sh
# Prepares writable dirs and framework caches, then drops to www-data (php-fpm keeps its own worker user).
set -e
cd /var/www/html
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown www-data:www-data storage storage/app storage/framework storage/logs bootstrap/cache 2>/dev/null || true

if [ "${APP_ENV:-}" = "production" ]; then
    su-exec www-data sh -c 'php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache' >/dev/null
fi

if [ "$1" = "php-fpm" ]; then
    exec "$@"
fi
exec su-exec www-data "$@"
