#!/bin/sh
echo ">>> Démarrage de Nginx..."

SSL_CERT="/etc/letsencrypt/live/app.m-motors.clickandgo-informatique.com/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/app.m-motors.clickandgo-informatique.com/privkey.pem"

rm -f /etc/nginx/conf.d/*.conf

cp /etc/nginx/conf.d/default.conf.disabled /etc/nginx/conf.d/active.conf

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
  echo ">>> Certificat trouvé, activation de HTTPS"
  cp /etc/nginx/conf.d/ssl.conf.disabled /etc/nginx/conf.d/active-ssl.conf
else
  echo ">>> Aucun certificat trouvé, démarrage en HTTP uniquement"
fi

nginx -g "daemon off;"
