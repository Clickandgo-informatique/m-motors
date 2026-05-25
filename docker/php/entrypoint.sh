#!/bin/sh
set -e

echo "Fixing permissions..."
chown -R www-data:www-data /var/www/var /var/www/vendor

# Installer les dépendances Composer si vendor est absent ou incomplet
if [ ! -d /var/www/vendor ] || [ ! -f /var/www/vendor/autoload.php ]; then
    echo "Vendor is missing — installing Composer dependencies..."
    cd /var/www
    composer install --no-interaction --optimize-autoloader
fi

# Export PostgreSQL password
export PGPASSWORD="$POSTGRES_PASSWORD"

# Attendre PostgreSQL
until pg_isready -h db -p 5432 -U "${POSTGRES_USER:-app}"; do
  echo "Waiting for PostgreSQL..."
  sleep 1
done

# Lancer commande container
exec "$@"