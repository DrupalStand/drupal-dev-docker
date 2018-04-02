# php:5.6.29-apache
FROM php:7.1-apache

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
RUN pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && docker-php-ext-install -j$(nproc) iconv mcrypt \
  && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
  && docker-php-ext-install -j$(nproc) gd pdo_mysql curl mbstring opcache zip \
  && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.remote_autostart=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  # Remote connect_back only works on Linux systems because OSX abstracts it away into a VM
  && echo 'xdebug.remote_connect_back="${CONNECTBACK}"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.remote_host=192.168.65.1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN apt-get update \
  && apt-get install -y libmemcached11 libmemcachedutil2 build-essential libmemcached-dev libz-dev \
  && pecl install memcached \
  && echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini \
  && apt-get remove -y build-essential libmemcached-dev libz-dev \
  && apt-get autoremove -y \
  && apt-get clean \
  && rm -rf /tmp/pear

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set timezone.
RUN echo "date.timezone = \"$PHP_TIMEZONE\"" > /usr/local/etc/php/conf.d/timezone.ini

RUN a2enmod rewrite && a2enmod ssl && a2ensite default-ssl

COPY ssl-cert-snakeoil.pem /etc/ssl/certs/ssl-cert-snakeoil.pem
COPY ssl-cert-snakeoil.key /etc/ssl/private/ssl-cert-snakeoil.key
COPY php.ini /usr/local/etc/php/

EXPOSE 443

RUN [ -e /etc/apache2/sites-enabled/000-default.conf ] && sed -i -e "s/\/var\/www\/html/\/var\/www\/web/" /etc/apache2/sites-enabled/000-default.conf \
  || sed -i -e "s/\/var\/www\/html/\/var\/www\/web/" /etc/apache2/apache2.conf \
  && [ -e /etc/apache2/sites-enabled/default-ssl.conf ] && sed -i -e "s/\/var\/www\/html/\/var\/www\/web/" /etc/apache2/sites-enabled/default-ssl.conf

WORKDIR /var/www
