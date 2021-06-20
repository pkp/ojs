FROM php:7.3-apache

ENV SERVERNAME="localhost"      \
	OJS_CLI_INSTALL="0"         \
	OJS_DB_HOST="mariadb"     \
	OJS_DB_USER="root"           \
	OJS_DB_PASSWORD=""       \
	OJS_DB_NAME="ojs"           \
	OJS_CONF="/var/www/html/config.inc.php"

COPY . /var/www/html

WORKDIR /var/www/html

RUN apt-get update
RUN apt-get install -y git

RUN cp config.TEMPLATE.inc.php config.inc.php

RUN apt-get install sudo 
RUN apt-get install zip unzip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');";
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer;
RUN yes |composer --working-dir=lib/pkp install --ignore-platform-reqs
RUN yes | composer --working-dir=plugins/paymethod/paypal install
RUN yes | composer --working-dir=plugins/generic/citationStyleLanguage install --ignore-platform-reqs

RUN curl -sL https://deb.nodesource.com/setup_14.x | sudo -E bash - 
RUN apt-get install -y nodejs 
RUN npm install
RUN npm run build

RUN docker-php-ext-install mysqli

RUN mkdir /var/www/files
RUN sudo chown -R www-data:www-data /var/www/*

EXPOSE 80
EXPOSE 443

