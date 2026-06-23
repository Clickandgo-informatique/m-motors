#!/bin/sh
set -e

# Répertoire de l'application dans le container
APP_DIR="/var/www"

echo "Starting entrypoint..."

# Correction des permissions Symfony pour le cache et les logs
if [ -d "$APP_DIR/var" ]; then
    chown -R www-data:www-data "$APP_DIR/var"
fi

# Correction des permissions du dossier public/uploads
mkdir -p "$APP_DIR/public/uploads"
mkdir -p "$APP_DIR/public/uploads/vehicles"

chown -R www-data:www-data "$APP_DIR/public/uploads"
chmod -R 775 "$APP_DIR/public/uploads"

# Correction des permissions du vendor si déjà présent
if [ -d "$APP_DIR/vendor" ]; then
    chown -R www-data:www-data "$APP_DIR/vendor"
fi

# Installation automatique des dépendances si vendor absent ou incomplet
if [ ! -d "$APP_DIR/vendor" ] || [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "vendor missing, running composer install"
    cd "$APP_DIR"
    composer install --no-interaction --optimize-autoloader
fi

# Export du mot de passe PostgreSQL pour psql / pg_isready
export PGPASSWORD="$POSTGRES_PASSWORD"

# Attente de disponibilité de PostgreSQL
echo "waiting for postgres..."
until pg_isready -h db -p 5432 -U "$POSTGRES_USER"; do
    sleep 1
done

echo "postgres is ready"

# Création de la base de test si elle n'existe pas
echo "ensuring test database exists"

psql -h db -U "$POSTGRES_USER" -d postgres -tAc \
"SELECT 1 FROM pg_database WHERE datname='m_motors_test'" | grep -q 1 || \
psql -h db -U "$POSTGRES_USER" -d postgres -c "CREATE DATABASE m_motors_test"

echo "test database ready"

# Exécution de la commande principale du container (php-fpm ou autre)
exec "$@"