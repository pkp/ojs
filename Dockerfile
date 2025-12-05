FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    unzip \
    git \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p files cache && chmod -R 777 files cache public

COPY nginx.conf /etc/nginx/nginx.conf

CMD service nginx start && php-fpm
