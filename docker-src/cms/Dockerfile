#------------------------------------------------------------------------------
# Composer PROD
#------------------------------------------------------------------------------
FROM composer:latest as composer-prod


# instlal tool to make composer download packages in parallel
RUN  composer global require hirak/prestissimo
#RUN  composer global require hirak/prestissimo \
#  # Get the drush and script directories from docker-composer
#  && composer create-project \
#       drupal-composer/drupal-project:8.x-dev \
#       /var/www \
#       --ignore-platform-reqs \
#       --prefer-dist \
#       --remove-vcs \
#  && rm -rf \
#       /var/www/web \
#       /var/www/vendor \
#       /var/www/composer.json \
#       /var/www/composer.lock

RUN mkdir -p /var/www/webroot/core \
      /var/www/webroot/libraries \
      /var/www/webroot/modules/contrib \
      /var/www/webroot/profiles/contrib \
      /var/www/webroot/themes/contrib \
      /var/www/webroot/sites/all/drush/contrib \
      /var/www/vendor

COPY composer.json composer.lock /var/www/
COPY scripts /var/www/scripts

WORKDIR /var/www

# The following flag breaks drupal --classmap-authoritative
RUN  composer install \
       --ignore-platform-reqs \
       --optimize-autoloader \
       --no-interaction \
       --no-progress \
       --prefer-dist \
       --no-scripts \
       --no-ansi \
       --no-dev
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

COPY docker-src/cms/php-conf.d/* /usr/local/etc/php/conf.d/

COPY webroot /var/www/webroot

RUN  rm -rf \
       webroot/core/* \
       webroot/libraries/* \
       webroot/modules/contrib/* \
       webroot/profiles/contrib/* \
       webroot/themes/contrib/* \
       webroot/sites/all/drush/contrib/*

COPY --from=composer-prod /var/www/webroot/core webroot/core
COPY --from=composer-prod /var/www/webroot/libraries webroot/libraries
COPY --from=composer-prod /var/www/webroot/modules/contrib webroot/modules/contrib
COPY --from=composer-prod /var/www/webroot/profiles/contrib webroot/profiles/contrib
COPY --from=composer-prod /var/www/webroot/themes/contrib webroot/themes/contrib
COPY --from=composer-prod /var/www/webroot/sites/all/drush/contrib webroot/sites/all/drush/contrib
COPY --from=composer-prod /var/www/scripts scripts
COPY --from=composer-prod /var/www/vendor vendor
COPY phpunit.xml.dist /var/www/

WORKDIR /var/www/webroot

RUN ln -s /var/www/vendor/bin/* /bin/

#------------------------------------------------------------------------------
# WEB PROD
#------------------------------------------------------------------------------
FROM nginx:stable-alpine as web-prod

# This will ensure that no matter what the files directory will be empty
RUN mkdir -p /var/www/webroot/sites/default/files

COPY --from=php-prod /var/www/webroot /var/www/webroot

RUN rm /etc/nginx/conf.d/default.conf
COPY ./docker-src/cms/nginx/drupal.conf /etc/nginx/conf.d/drupal.conf

WORKDIR /var/www/webroot

#------------------------------------------------------------------------------
# PHP DEV
#------------------------------------------------------------------------------
FROM scratch as php-dev
COPY --from=php-prod . /

WORKDIR /var/www

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
  && apk del .build-deps

COPY docker-src/cms/php-conf.d.dev/* /usr/local/etc/php/conf.d/

RUN ln -sf /var/www/vendor/bin/* /bin/

WORKDIR /var/www/webroot

# this doesn't work. nor with &&
#ENTRYPOINT ["ln", "-sf", "/var/www/vendor/bin/*", "/bin/", "&", "docker-php-entrypoint"]
ENTRYPOINT ["docker-php-entrypoint"]

CMD ["php-fpm"]
