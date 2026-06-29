#!/bin/sh
set -e

cd /var/www/html

if [ -f artisan ]; then
    php artisan package:discover --ansi 2>/dev/null || true
    php artisan filament:assets --ansi 2>/dev/null || true
fi

# Swarm: nginx читает статику из общего volume
if [ -d /shared-public ] && [ -d public ]; then
    mkdir -p /shared-public
    cp -a public/. /shared-public/
fi

if [ -d storage ] && [ -d bootstrap/cache ]; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"
