FROM php:8.4-fpm

# Installer les dépendances système nécessaires à Symfony et PHP extensions
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    postgresql-client \
    libgd-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    && git config --system --add safe.directory /var/www \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_pgsql \
        zip \
        gd \
    && rm -rf /var/lib/apt/lists/*

# Installer Redis via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Installer Xdebug (dev uniquement, à désactiver en prod si besoin)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copier Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier l'entrypoint dans le conteneur
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Définir l'entrypoint principal du container
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Lancer PHP-FPM en foreground (obligatoire pour Docker)
CMD ["php-fpm", "-F"]