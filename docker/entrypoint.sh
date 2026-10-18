#!/bin/sh
set -e

# Prepare the app on container start: cache config and run migrations.
if [ "$1" = "apache2-foreground" ]; then
    php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
