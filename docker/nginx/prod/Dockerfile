FROM nginx:latest

COPY docker/nginx/prod/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/prod/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

