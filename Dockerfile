FROM php:8.2-apache

# Extensiones necesarias (PDO MySQL + GD para imágenes) y mod_rewrite
RUN apt-get update \
 && apt-get install -y libjpeg-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install gd pdo pdo_mysql \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# DocumentRoot = /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Permitir .htaccess en /public (AllowOverride All)
RUN set -eux; \
  { \
    echo '<Directory "${APACHE_DOCUMENT_ROOT}">'; \
    echo '    Options Indexes FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '</Directory>'; \
  } > /etc/apache2/conf-available/public-dir.conf; \
  a2enconf public-dir

WORKDIR /var/www/html
COPY . /var/www/html

# Carpeta de subidas dentro de public (para demo sin disco)
RUN mkdir -p public/uploads/usuarios && chown -R www-data:www-data public/uploads

EXPOSE 10000
