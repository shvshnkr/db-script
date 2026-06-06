ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev \
    git unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli mbstring gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/conf.d/dbscript.ini
COPY apache-vhost.conf /etc/apache2/conf-available/dbscript.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN a2enconf dbscript \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
