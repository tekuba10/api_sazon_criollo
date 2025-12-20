FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
