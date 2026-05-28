FROM nginx:alpine

# Copier la config Nginx
COPY docker/nginx/prod/default.conf /etc/nginx/conf.d/default.conf

# Copier l'entrypoint
COPY docker/nginx/prod/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
