FROM alpine:3.13
ARG version
ARG apk_repo
ARG php_version

COPY . /var/www/html
WORKDIR /var/www/html

RUN apk update;
RUN apk add --update \
  sudo \
  curl \
  libcurl \
  ca-certificates \
  php7-fpm \
  php7-ctype \
  php7-curl \
  php7-intl \
  php7-json \
  php7-mbstring \
  php7-mysqli \
  php7-mysqlnd \
  php7-opcache \
  php7-pecl-redis \
  php7-pecl-apcu \
  php7-pdo \
  php7-pdo_mysql \
  php7-session \
  php7-pdo_sqlite \
  php7-xml;

RUN apk add --update \
  git \
  nodejs \
  npm \
  php7 \
  php7-dom \
  php7-phar \
  php7-simplexml \
  php7-tokenizer \
  php7-xmlwriter 

RUN rm -rf /var/cache/apk/* /tmp/pear ~/.pearrc;

# Sane defaults
RUN set -ex; \
  sed -ri \
    -e 's|^;?expose_php = .*|expose_php = off|' \
    -e 's|^;?allow_url_fopen = .*|allow_url_fopen = on|' \
    -e 's|^;?opcache.error_log=.*|opcache.error_log = /proc/self/fd/2|' \
    -e 's|^;?opcache.fast_shutdown=.*|opcache.fast_shutdown = 1|' \
    /etc/php7/php.ini;

# Installing composer
RUN php7 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');";
RUN php7 composer-setup.php --install-dir=/usr/local/bin --filename=composer;
RUN rm -f composer-setup.php;
RUN ln -s -f /usr/bin/php7 /usr/bin/php
RUN cp config.TEMPLATE.inc.php config.inc.php
RUN yes | composer --working-dir=lib/pkp install --ignore-platform-reqs
RUN yes | composer --working-dir=plugins/paymethod/paypal install
RUN yes | composer --working-dir=plugins/generic/citationStyleLanguage install --ignore-platform-reqs

RUN sudo npm install
RUN sudo npm run build

RUN mkdir /var/www/files


