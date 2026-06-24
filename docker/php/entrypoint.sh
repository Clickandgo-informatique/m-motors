#!/bin/sh
set -e

APP_DIR="/var/www"

echo "Starting entrypoint..."

# permissions Symfony
if [ -d "$APP_DIR/var" ]; then
    chown -R www-data:www-data "$APP_DIR/var"
fi

# uploads
mkdir -p "$APP_DIR/public/uploads/vehicles"
chown -R www-data:www-data "$APP_DIR/public/uploads"
chmod -R 775 "$APP_DIR/public/uploads"

# vendor permissions
if [ -d "$APP_DIR/vendor" ]; then
    chown -R www-data:www-data "$APP_DIR/vendor"
fi

# composer install si besoin
if [ ! -d "$APP_DIR/vendor" ] || [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    echo "vendor missing, running composer install"
    cd "$APP_DIR"
    composer install --no-interaction --optimize-autoloader
fi

# postgres env safe defaults
POSTGRES_HOST="${POSTGRES_HOST:-db}"
POSTGRES_USER="${POSTGRES_USER:-app}"
POSTGRES_DB="${POSTGRES_DB:-app}"

echo "waiting for postgres..."

# attente postgres SANS password prompt
until pg_isready -h "$POSTGRES_HOST" -p 5432 -U "$POSTGRES_USER" >/dev/null 2>&1; do
    sleep 1
done

echo "postgres is ready"

# IMPORTANT: pas de prompt interactif psql
export PGPASSWORD="${POSTGRES_PASSWORD:-app}"

echo "ensuring test database exists"

psql -h "$POSTGRES_HOST" -U "$POSTGRES_USER" -d postgres -v ON_ERROR_STOP=1 <<EOF
SELECT 'CREATE DATABASE m_motors_test'
WHERE NOT EXISTS (
    SELECT FROM pg_database WHERE datname = 'm_motors_test'
)\gexec
EOF

echo "test database ready"

# run container command
exec "$@"