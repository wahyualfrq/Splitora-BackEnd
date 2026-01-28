FROM php:8.2-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    python3 \
    python3-pip \
    build-essential \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# PHP upload & runtime config (INI KUNCI)
RUN echo "file_uploads=On" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "upload_tmp_dir=/tmp" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Application source
COPY . .

# PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Python dependencies
RUN pip3 install --break-system-packages \
    pandas \
    PyPDF2 \
    openpyxl

# Permissions + folders (WAJIB)
RUN mkdir -p storage/app/upload storage/app/tmp \
 && chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
