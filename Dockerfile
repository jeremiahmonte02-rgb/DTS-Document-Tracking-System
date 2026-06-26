# === STAGE 1: COMPILE FRONTEND ASSETS ===
FROM node:20-alpine AS asset-builder
WORKDIR /app
COPY src/package*.json ./
RUN npm install
COPY . .
WORKDIR /app/src
RUN npm run build

# === STAGE 2: PRODUCTION RUNTIME ===
FROM php:8.3-apache
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd
RUN a2enmod rewrite
RUN sed -i 's|/var/www/html|/var/www/html/src/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|/var/www/html/src/public|g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .
COPY --from=asset-builder /app/src/public/build ./src/public/build

WORKDIR /var/www/html/src
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN chown -R www-data:www-data /var/www/html/src/storage /var/www/html/src/bootstrap/cache

RUN php src/artisan config:cache

EXPOSE 80
WORKDIR /var/www/html
CMD ["apache2-foreground"]
