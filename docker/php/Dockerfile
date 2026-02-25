FROM php:8.4-fpm

# Harmoniser www-data avec l'utilisateur hôte (UID 1000)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Créer les dossiers Symfony et donner les droits
RUN mkdir -p /var/www/var && chown -R www-data:www-data /var/www

# Install packages
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev zip \
    postgresql-client \
    && docker-php-ext-install intl pdo pdo_pgsql zip

# Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------
# 🔥 ENTRYPOINT : copie du script + droits d'exécution
# ---------------------------------------------------------
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ---------------------------------------------------------

# ---------------------------------------------------------
# 🔥 Définir l’entrypoint
# ---------------------------------------------------------
ENTRYPOINT ["entrypoint.sh"]

# Commande par défaut
CMD ["php-fpm"]
