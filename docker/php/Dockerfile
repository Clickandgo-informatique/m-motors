FROM php:8.4-fpm

# Harmoniser www-data avec l'utilisateur hôte (UID 1000)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Créer les dossiers Symfony et donner les droits
RUN mkdir -p /var/www/var && chown -R www-data:www-data /var/www

# Install packages
RUN apt-get update && apt-get install -y \
    git unzip \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    postgresql-client \
    libgd-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
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

# Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ENTRYPOINT
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]