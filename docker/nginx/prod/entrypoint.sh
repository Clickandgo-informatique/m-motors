#!/bin/sh

echo ">>> Starting Nginx..."

# Toujours démarrer nginx même sans cert
nginx -g "daemon on;"

# Attendre certbot
echo ">>> waiting for certificates..."
sleep 5

nginx -s reload

echo ">>> Nginx ready"

tail -f /var/log/nginx/access.log