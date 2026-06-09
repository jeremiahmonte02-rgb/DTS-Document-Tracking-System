FROM php:8.3-apache

# Install basic system packages and PHP development extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache memory
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Docker PHP Extensions matching your document guidelines
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite module for Laravel routing syntax
RUN a2enmod rewrite

# Re-route Apache's default site configuration to look inside /public
# RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Get latest secure Composer build layer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setup the web document working root inside the container
WORKDIR /var/www/html
