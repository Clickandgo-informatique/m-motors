#!/bin/sh
set -e

echo "Fixing permissions..."
chown -R www-data:www-data /var/www

# Installer les dépendances Composer si vendor/ est vide
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "Vendor is empty — installing Composer dependencies..."
    cd /var/www
    composer install --no-interaction --optimize-autoloader
fi

# Export du mot de passe PostgreSQL pour éviter les prompts
export PGPASSWORD="$POSTGRES_PASSWORD"

# Attendre que PostgreSQL soit prêt
until pg_isready -h db -p 5432 -U "$POSTGRES_USER"; do
  echo "Waiting for PostgreSQL..."
  sleep 1
done

# Lancer la commande passée au container
exec "$@"