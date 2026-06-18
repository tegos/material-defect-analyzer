#!/bin/sh
set -e

php artisan storage:link --quiet 2>/dev/null || true
php artisan migrate --force --quiet

exec php-fpm
