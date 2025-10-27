FROM php:8.2-apache

# Extensiones: PDO MySQL + GD (imágenes)
RUN apt-get update \
 && apt-get install -y libjpeg-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install gd pdo pdo_mysql \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html


RUN mkdir -p uploads/usuarios && chown -R www-data:www-data uploads

EXPOSE 10000
