FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql

# 🔧 Configuración de PHP para uploads
RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_input_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
