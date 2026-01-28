FROM php:8.2-cli

# Install system dependencies (WAJIB untuk pandas)
RUN apt-get update && apt-get install -y \
    git unzip zip \
    python3 python3-pip \
    build-essential \
    libpq-dev \
    libzip-dev \
    curl \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy project
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Upgrade pip (PENTING)
RUN pip3 install --upgrade pip

# Install Python dependencies (AMAN)
RUN pip3 install pandas PyPDF2 openpyxl

# Permission
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
