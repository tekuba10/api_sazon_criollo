FROM php:8.2-fpm

# =========================
# Dependencias del sistema
# =========================
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    curl \
    cron \
    supervisor \
    procps \
    ghostscript \
    imagemagick \
    libmagickwand-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-enable pdo_pgsql \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# =========================
# Permitir PDFs en ImageMagick
# =========================
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
        sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml; \
    fi

RUN if [ -f /etc/ImageMagick-7/policy.xml ]; then \
        sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-7/policy.xml; \
    fi

# =========================
# Configuración PHP
# =========================
RUN echo "upload_max_filesize=100M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_input_time=300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini

# =========================
# CRON
# =========================
COPY campaniascron /etc/cron.d/campaniascron
RUN chmod 0644 /etc/cron.d/campaniascron
RUN touch /var/log/campanias_cron.log

# =========================
# SUPERVISOR
# =========================
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# =========================
# Composer
# =========================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================
# Directorio de trabajo
# =========================
WORKDIR /var/www/html

# =========================
# Comando final
# =========================
CMD ["/usr/bin/supervisord"]
