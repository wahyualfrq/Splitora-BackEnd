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

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Application source
COPY . .

# PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Python dependencies (Debian Bookworm fix)
RUN pip3 install --break-system-packages \
    pandas \
    PyPDF2 \
    openpyxl

# Permissions
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
