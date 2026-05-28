FROM nginx:alpine

COPY docker/nginx/prod/default.conf /etc/nginx/conf.d/default.conf.disabled
COPY docker/nginx/prod/ssl.conf /etc/nginx/conf.d/ssl.conf.disabled
COPY docker/nginx/prod/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
