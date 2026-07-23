# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxslt1-dev \
    default-mysql-client \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Configure GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install required PHP extensions
RUN docker-php-ext-install \
    mysqli \
    pdo \
    pdo_mysql \
    gd \
    intl \
    mbstring \
    xml \
    bcmath \
    ftp \
    zip \
    xsl

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Set the working directory
WORKDIR /var/www/html

# Copy the OJS source code into the container
COPY . /var/www/html/

# Install Composer dependencies for PKP library
WORKDIR /var/www/html/lib/pkp
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Go back to the OJS root directory
WORKDIR /var/www/html

# Build frontend assets if package.json exists
RUN if [ -f "package.json" ]; then \
    npm install && npm run build; \
    fi

# Set the correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]