FROM php:7.0-apache

RUN apt-get update && apt-get install -y \
  vim \
  git \
  unzip \
  wget \
  curl \
  libmcrypt-dev \
  libgd2-dev \
  libgd2-xpm-dev \
  libcurl4-openssl-dev \
  mysql-client

ENV PHP_TIMEZONE America/New_York

# Install extensions
RUN docker-php-ext-install -j$(nproc) iconv mcrypt \
  && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
  && docker-php-ext-install -j$(nproc) gd pdo_mysql curl mbstring opcache

# Install Composer & add global vendor bin dir to $PATH
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN echo 'export PATH="$PATH:/root/.composer/vendor/bin"' >> $HOME/.bashrc

# Set timezone.
RUN echo "date.timezone = \"$PHP_TIMEZONE\"" > /usr/local/etc/php/conf.d/timezone.ini

RUN a2enmod rewrite

RUN [ -e /etc/apache2/sites-enabled/000-default.conf ] && sed -i -e "s/\/var\/www\/html/\/var\/www\/web/" /etc/apache2/sites-enabled/000-default.conf || sed -i -e "s/\/var\/www\/html/\/var\/www\/web/" /etc/apache2/apache2.conf
