#!/bin/sh
set -e

echo ">>> Starting Nginx..."

SSL_DIR="/etc/letsencrypt/live/app.m-motors.clickandgo-informatique.com"

rm -f /etc/nginx/conf.d/default.conf

if [ -f "$SSL_DIR/fullchain.pem" ] && [ -f "$SSL_DIR/privkey.pem" ]; then
    echo ">>> SSL certificate found"
    cp /etc/nginx/conf.d/ssl.conf.disabled /etc/nginx/conf.d/default.conf
else
    echo ">>> No certificate found, HTTP mode"
    cp /etc/nginx/conf.d/default.conf.disabled /etc/nginx/conf.d/default.conf
fi

nginx -t

exec nginx -g "daemon off;"