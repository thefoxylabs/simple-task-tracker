#!/bin/sh
set -e

# Prepare the app on container start: cache config and run migrations.
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "$1" = "apache2-foreground" ]; then
    php artisan migrate --force

    # On a fresh install, print the token needed to create the first admin.
    php artisan app:setup-token
fi

# Files created above (e.g. the setup token) must be readable by Apache.
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
