FROM php:8.2-cli

# Install system deps + python
RUN apt-get update && apt-get install -y \
    git unzip zip \
    python3 python3-pip \
    libzip-dev \
    && docker-php-ext-install zip

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy project
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Install python deps
RUN pip3 install pandas PyPDF2 openpyxl

# Permission
RUN chmod -R 777 storage bootstrap/cache

# Expose port
EXPOSE 8000

# Start Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
