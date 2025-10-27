# =========================
# Etapa 1: dependencias con Composer
# =========================
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# =========================
# Etapa 2: PHP + Apache
# =========================
FROM php:8.2-apache

# Extensiones y mod_rewrite
RUN docker-php-ext-install pdo pdo_mysql \
 && a2enmod rewrite

# DocumentRoot = /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Código del proyecto
COPY . /var/www/html

# Dependencias de Composer
COPY --from=vendor /app/vendor /var/www/html/vendor

# Carpeta de subidas 
RUN mkdir -p uploads/usuarios \
 && chown -R www-data:www-data uploads

EXPOSE 10000
