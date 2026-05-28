FROM nginx:latest
RUN apt-get update && apt-get install -y certbot
COPY docker/nginx/prod/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
