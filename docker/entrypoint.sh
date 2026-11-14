#!/bin/sh
set -e

cd /var/www/html

# Ensure Laravel writable directories exist (critical when host dirs are bind-mounted)
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/run/php

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/run/php
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy .env.example to .env on first boot if missing
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Install Composer dependencies if vendor volume is empty
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Generate APP_KEY if empty in .env
if [ -f /var/www/html/.env ]; then
    if grep -qE '^APP_KEY=[[:space:]]*$' /var/www/html/.env || ! grep -q '^APP_KEY=' /var/www/html/.env; then
        php /var/www/html/artisan key:generate --force
    fi
fi

# Clear stale cached config/routes/views across rebuilds
if [ -f /var/www/html/artisan ]; then
    php /var/www/html/artisan optimize:clear || true
fi

# Re-ensure ownership after artisan CLI commands run as root
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec "$@"
