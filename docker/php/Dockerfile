FROM php:8.2-fpm

# =========================
# Dependencias del sistema
# =========================
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    curl \
    cron \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql

# =========================
# Configuración PHP
# =========================
RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_input_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

# =========================
# CRON
# =========================

# Copiar archivo cron
COPY campanias-cron /etc/cron.d/campanias-cron

# Permisos + registrar cron
RUN chmod 0644 /etc/cron.d/campanias-cron \
    && crontab /etc/cron.d/campanias-cron

# Crear archivo de log
RUN touch /var/log/campanias_cron.log

# =========================
# Limpieza
# =========================
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# =========================
# Iniciar cron + php-fpm
# =========================
CMD cron && php-fpm
