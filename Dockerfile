FROM php:8.2-apache

Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

Habilitar mod_rewrite
RUN a2enmod rewrite

Copiar configuración de Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

Copiar código de la aplicación
COPY . /var/www/html/

Crear directorios de uploads con permisos correctos
RUN mkdir -p /var/www/html/uploads \
    && mkdir -p /var/www/html/views/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/views/uploads

Quitar el archivo debug.log si existe
RUN rm -f /var/www/html/debug.log

EXPOSE 80

CMD ["apache2-foreground"]
