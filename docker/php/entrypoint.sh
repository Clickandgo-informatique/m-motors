#!/bin/sh
set -e

echo "Fixing permissions..."
chown -R www-data:www-data /var/www

exec "$@"

