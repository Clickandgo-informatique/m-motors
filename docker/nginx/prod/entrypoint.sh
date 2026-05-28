#!/bin/sh
echo ">>> Démarrage de Nginx..."

SSL_CERT="/etc/letsencrypt/live/app.m-motors.clickandgo-informatique.com/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/app.m-motors.clickandgo-informatique.com/privkey.pem"

# S'assurer que le dossier existe
mkdir -p /etc/nginx/conf.d

# Nettoyer les anciennes conf actives
rm -f /etc/nginx/conf.d/active.conf
rm -f /etc/nginx/conf.d/active-ssl.conf

# Toujours charger la conf HTTP
cp /etc/nginx/conf.d/default.conf /etc/nginx/conf.d/active.conf

# Si le certificat existe, activer HTTPS
if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
  echo ">>> Certificat trouvé, activation de HTTPS"
  cp /etc/nginx/conf.d/ssl.conf /etc/nginx/conf.d/active-ssl.conf
else
  echo ">>> Aucun certificat trouvé, démarrage en HTTP uniquement"
fi

nginx -g "daemon off;"
