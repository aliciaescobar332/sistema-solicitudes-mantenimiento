# ---------------------------------------------------------
# Imagen PHP-FPM con la app Laravel
# (El frontend ya viene compilado en public/build,
#  generado localmente con "npm run build" antes de este paso)
# ---------------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Dependencias del sistema y extensiones PHP necesarias para Laravel + MySQL
RUN apk add --no-cache \
        bash \
        git \
        curl \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos el código de la app (incluye public/build ya compilado)
COPY . .

# Instalamos dependencias PHP (sin dev, optimizado para producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos para storage y cache de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
