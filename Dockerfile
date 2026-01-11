# ---------- STAGE 1: dependencies ----------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json ./
RUN composer install \
    --no-interaction \
    --prefer-dist

# ---------- STAGE 2: runtime ----------
FROM php:8.2-apache

# System deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
 && docker-php-ext-install \
    pdo \
    pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Apache
RUN a2enmod rewrite

# Document root -> public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

# App source
COPY . .

# Vendor from build stage
COPY --from=vendor /app/vendor ./vendor

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
