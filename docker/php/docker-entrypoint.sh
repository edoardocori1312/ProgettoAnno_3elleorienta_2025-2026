#!/bin/sh
set -e

mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads 2>/dev/null || true
chmod -R 777 /var/www/html/uploads

php /var/www/html/db/make_seed_images.php

exec "$@"
