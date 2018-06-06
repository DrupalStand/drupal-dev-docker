#------------------------------------------------------------------------------
# Composer PROD
#------------------------------------------------------------------------------
FROM composer:latest as composer-prod

# Get the drush and script directories from docker-composer
RUN  composer create-project \
       drupal-composer/drupal-project:8.x-dev \
       /var/www \
       --ignore-platform-reqs \
       --prefer-dist \
       --remove-vcs \
  && rm -rf \
       /var/www/web \
       /var/www/vendor \
       /var/www/composer.json \
       /var/www/composer.lock

COPY ./composer.json /var/www/composer.json
COPY ./composer.lock /var/www/composer.lock

WORKDIR /var/www

# The following flag breaks drupal 8--classmap-authoritative
RUN  composer install \
       --ignore-platform-reqs \
       --optimize-autoloader \
       --no-interaction \
       --no-progress \
       --prefer-dist \
       --no-scripts \
       --no-ansi \
       --no-dev
RUN  composer drupal-scaffold

#------------------------------------------------------------------------------
# PHP PROD
#------------------------------------------------------------------------------

FROM php:7.1-fpm-alpine as php-prod

# Set timezone.
ENV PHP_TIMEZONE America/New_York
RUN echo "date.timezone = \"$PHP_TIMEZONE\"" > /usr/local/etc/php/conf.d/timezone.ini

RUN cat /dev/urandom | LC_CTYPE=C tr -dc 'a-zA-Z0-9' | fold -w 64 | head -n 1 > /var/www/salt.txt

WORKDIR /var/www

RUN apk add --no-cache --virtual .build-deps \
             # unknown needed
             autoconf \
             g++ \
             pcre-dev \
             libtool \
             make \
             curl \
             git \
             # Needed base depend
             coreutils

# Install PHP memcached extension
# look at following for PHP 7.2 https://stackoverflow.com/a/41575677
RUN set -xe \
    && apk add --no-cache --update libmemcached-libs zlib mysql-client \
    && apk add --no-cache --update --virtual .memcached-build-deps \
               zlib-dev \
               libmemcached-dev \
               cyrus-sasl-dev \
    && git clone -b php7 https://github.com/php-memcached-dev/php-memcached /usr/src/php/ext/memcached \
    && docker-php-ext-configure /usr/src/php/ext/memcached \
        --disable-memcached-sasl \
    && docker-php-ext-install /usr/src/php/ext/memcached \
    && rm -rf /usr/src/php/ext/memcached \
    # Cleanup
    && rm -rf /tmp/* ~/.pearrc /usr/share/php7 \
    && docker-php-source delete \
    && apk del .memcached-build-deps

# install the PHP extensions we need
# postgresql-dev is needed for https://bugs.alpinelinux.org/issues/3642
RUN set -ex \
  && apk add --no-cache --virtual .build-deps2 \
             # GD depends
             freetype-dev \
             libjpeg-turbo-dev \
             libpng-dev \
             # mcrypt depends
             libmcrypt-dev \
             # xmlrpc depends
             libxml2-dev \
             libxslt-dev \
             postgresql-dev \
  # Configure and Install PHP extensions
  && docker-php-ext-configure gd  \
       --with-freetype-dir=/usr/include/ \
       --with-jpeg-dir=/usr/include/ \
       --with-png-dir=/usr/include/ \
  && docker-php-ext-install -j "$(nproc)" \
             gd \
             iconv \
             mcrypt \
             mysqli \
             opcache \
             pdo_mysql \
             pdo_pgsql \
             xmlrpc \
             xsl \
             zip \
  && runDeps="$( \
    scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
      | tr ',' '\n' \
      | sort -u \
      | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
  )" \
  && apk add --virtual .drupal-phpexts-rundeps $runDeps \
  # Cleanup
  && rm -rf /tmp/pear ~/.pearrc \
  && chown -R www-data:www-data /usr/local/var/log \
  && docker-php-source delete \
  && apk del .build-deps .build-deps2 \
  && rm -rf /tmp/* /var/cache/apk/*

# recommended PHP.ini settings: https://secure.php.net/manual/en/opcache.installation.php
# with prod modifcations to turn cache files only once
RUN { \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=8'; \
      echo 'opcache.max_accelerated_files=4000'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.fast_shutdown=1'; \
      echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY docker-src/cms/php-conf.d/* /usr/local/etc/php/conf.d/

COPY --from=composer-prod /var/www/drupal /var/www/drupal
COPY --from=composer-prod /var/www/vendor /var/www/vendor
COPY --from=composer-prod /var/www/drush /var/www/drush

RUN ln -s /var/www/vendor/bin/* /bin/


# Create files directory
# Setting a volume will mean that any php run durring the build wont
# save cache files which may appear in the files dir
RUN  mkdir -p /var/www/drupal/sites/default/files \
  && chmod 777 /var/www/drupal/sites/default/files

COPY drupal /var/www/drupal

WORKDIR /var/www/drupal


#------------------------------------------------------------------------------
# WEB PROD
#------------------------------------------------------------------------------


FROM nginx:stable-alpine as web-prod

# This will ensure that no matter what the files directory will be empty
RUN mkdir -p /var/www/drupal/sites/default/files

COPY --from=php-prod /var/www/drupal /var/www/drupal

RUN rm /etc/nginx/conf.d/default.conf
COPY ./docker-src/cms/nginx/drupal.conf /etc/nginx/conf.d/drupal.conf


#------------------------------------------------------------------------------
# PHP DEV
#------------------------------------------------------------------------------


FROM scratch as php-dev
COPY --from=php-prod . /

WORKDIR /var/www

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN  apk add --no-cache --virtual \
             git \
             curl \
             vim \
             unzip \
             wget \
  && apk add --no-cache --virtual .build-deps \
             # unknown needed
             autoconf \
             g++ \
             pcre-dev \
             libtool \
             make \
             # Needed base depend
             coreutils \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.remote_autostart=on" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  # Remote connect_back only works on Linux systems because OSX abstracts it away into a VM
  && echo 'xdebug.remote_connect_back="${CONNECTBACK}"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.remote_host=192.168.65.1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del .build-deps \
  # recommended PHP.ini settings: https://secure.php.net/manual/en/opcache.installation.php
  && { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
  } > /usr/local/etc/php/conf.d/opcache.ini


COPY --from=composer-prod /var/www/scripts /var/www/scripts

COPY ./composer.json /var/www/composer.json
COPY ./composer.lock /var/www/composer.lock

RUN composer install --dev --ignore-platform-reqs --no-interaction --no-progress
RUN ln -sf /var/www/vendor/bin/* /bin/

WORKDIR /var/www/drupal

CMD ["php-fpm"]
