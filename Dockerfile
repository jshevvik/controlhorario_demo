FROM php:8.2-apache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Extensiones necesarias (PDO MySQL + GD para imágenes + mbstring para mPDF) y mod_rewrite
RUN apt-get update \
 && apt-get install -y \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    unzip \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install gd pdo pdo_mysql mbstring zip \
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

# Instalar dependencias de Composer (mPDF y otras librerías)
# Aumentar timeout y usar prefer-dist para descargar más rápido
RUN COMPOSER_PROCESS_TIMEOUT=600 composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    || (cat /root/.composer/cache/repo/https---repo.packagist.org/packages.json 2>/dev/null; exit 1)

# Crear directorio temporal para mPDF y carpeta de subidas
RUN mkdir -p tmp public/uploads/usuarios public/uploads/solicitudes \
 && chown -R www-data:www-data tmp public/uploads \
 && chmod -R 755 tmp public/uploads

EXPOSE 10000
