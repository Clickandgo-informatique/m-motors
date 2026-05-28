#!/bin/sh
set -e

echo ">>> Démarrage de Nginx..."
exec nginx -g "daemon off;"

