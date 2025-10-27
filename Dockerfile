# Imagen base: PHP + Apache
FROM php:8.2-apache

# Extensiones necesarias y mod_rewrite
RUN docker-php-ext-install pdo pdo_mysql \
 && a2enmod rewrite

# DocumentRoot = /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copiar el código del proyecto
COPY . /var/www/html

# Asegurar carpeta de subidas (si no usas disco persistente)
RUN mkdir -p uploads/usuarios \
 && chown -R www-data:www-data uploads

EXPOSE 10000
