#!/bin/sh
set -e

# fix permissions for symfony writable directories
chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

# install composer dependencies if vendor is missing or incomplete
if [ ! -d /var/www/html/vendor ] || [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "vendor is missing, installing composer dependencies"
    cd /var/www/html
    composer install --no-interaction --optimize-autoloader
fi

# export postgres password for psql commands
export PGPASSWORD="$POSTGRES_PASSWORD"

# wait for postgres to be available
until pg_isready -h db -p 5432 -U "$POSTGRES_USER"; do
    echo "waiting for postgres"
    sleep 1
done

echo "ensuring test database exists"

psql -h db -U "$POSTGRES_USER" -d postgres -tAc \
"SELECT 1 FROM pg_database WHERE datname='m_motors_test'" | grep -q 1 || \
psql -h db -U "$POSTGRES_USER" -d postgres -c "CREATE DATABASE m_motors_test"

echo "test database ready"

# execute container command
exec "$@"
