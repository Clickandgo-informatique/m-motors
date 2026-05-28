#!/bin/sh
set -e

DOMAIN="app.m-motors.clickandgo-informatique.com"
CERT_PATH="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"

# Si les certificats n'existent pas, on lance certbot
if [ ! -f "$CERT_PATH" ]; then
  echo ">>> Aucun certificat trouvé, lancement de Certbot..."
  certbot certonly --webroot \
    --webroot-path=/var/www/certbot \
    -d $DOMAIN \
    --email ton-email@example.com \
    --agree-tos \
    --non-interactive
fi

echo ">>> Certificats OK, démarrage de Nginx..."
exec nginx -g "daemon off;"
