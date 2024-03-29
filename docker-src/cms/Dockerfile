FROM alpine:edge as alpine-edge
RUN set -ex \
  && apk add --no-cache --virtual .build-bindfs \
                --repository=http://dl-cdn.alpinelinux.org/alpine/edge/testing/ \
                bindfs

#------------------------------------------------------------------------------
# PHP BASE
#------------------------------------------------------------------------------
FROM php:8.1-fpm-alpine as php-base

# Set timezone.
ENV PHP_TIMEZONE America/New_York
RUN echo "date.timezone = \"$PHP_TIMEZONE\"" > /usr/local/etc/php/conf.d/timezone.ini

RUN cat /dev/urandom | LC_CTYPE=C tr -dc 'a-zA-Z0-9' | fold -w 64 | head -n 1 > /var/www/salt.txt

WORKDIR /var/www

RUN apk add --no-cache --virtual .build-deps \
             # Misc dependencies
             autoconf \
             g++ \
             pcre-dev \
             libtool \
             make \
             curl \
             git \
             # Needed base depend
             coreutils

RUN apk add --no-cache --update mysql-client

# Install PHP memcached extension
# look at following for PHP 7.2 https://stackoverflow.com/a/41575677
RUN set -xe \
    && apk add --no-cache --update libmemcached-libs zlib \
    && apk add --no-cache --update --virtual .memcached-build-deps \
               zlib-dev \
               libmemcached-dev \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    # Cleanup
    && rm -rf /tmp/* ~/.pearrc /usr/share/php7 \
    && docker-php-source delete \
    && apk del .memcached-build-deps

# install the PHP extensions we need
RUN set -ex \
  && apk add --no-cache --virtual .build-php-exts \
             # GD depends
             freetype-dev \
             libjpeg-turbo-dev \
             libpng-dev \
             libwebp-dev \
             # xmlrpc depends
             libxml2-dev \
             libxslt-dev \
             libzip-dev \
  # must specify version due to it being in beta status
  && pecl install xmlrpc-1.0.0RC3 \
  && docker-php-ext-enable xmlrpc \
  # Configure and Install PHP extensions
  && docker-php-ext-configure gd  \
        --enable-gd \
        --with-freetype=/usr/include/ \
        --with-jpeg=/usr/include/ \
        --with-webp=/usr/include/ \
  && docker-php-ext-install -j "$(nproc)" \
             gd \
             mysqli \
             opcache \
             pdo_mysql \
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
  && apk del .build-deps .build-php-exts \
  && rm -rf /tmp/* /var/cache/apk/*

COPY docker-src/cms/php-conf.d/* /usr/local/etc/php/conf.d/

#------------------------------------------------------------------------------
# WEB DEV
#------------------------------------------------------------------------------
FROM nginx:stable-alpine as web-dev

RUN set -ex \
  && apk add --no-cache --virtual .build-bindfs \
                fuse3-libs fuse
COPY --from=alpine-edge /usr/bin/bindfs /usr/bin/bindfs
COPY docker-src/cms/90-bindfs-webroot.sh /docker-entrypoint.d/90-bindfs-webroot.sh
RUN chmod 774 /docker-entrypoint.d/90-bindfs-webroot.sh

RUN rm /etc/nginx/conf.d/default.conf
COPY ./docker-src/cms/nginx/ssl-cert-snakeoil.key /etc/nginx/private.key
COPY ./docker-src/cms/nginx/ssl-cert-snakeoil.pem /etc/nginx/public.pem
COPY ./docker-src/cms/nginx/drupal.conf /etc/nginx/conf.d/drupal.conf

WORKDIR /var/www/webroot

#------------------------------------------------------------------------------
# PHP DEV
#------------------------------------------------------------------------------
FROM scratch as php-dev
COPY --from=php-base . /

RUN set -ex \
  && apk add --no-cache --virtual .build-bindfs \
                fuse3-libs fuse
COPY --from=alpine-edge /usr/bin/bindfs /usr/bin/bindfs

ENV PHP_INI_DIR /usr/local/etc/php
ENV PHP_EXTRA_CONFIGURE_ARGS --enable-fpm --with-fpm-user=www-data --with-fpm-group=www-data --disable-cgi
ENV PHP_TIMEZONE America/New_York
ENV PHP_LDFLAGS -Wl,-O1 -Wl,--hash-style=both -pie
ENV PHP_CFLAGS -fstack-protector-strong -fpic -fpie -O2
ENV PHP_CPPFLAGS -fstack-protector-strong -fpic -fpie -O2

COPY docker-src/cms/entrypoint /usr/local/bin/drupalstand-entrypoint
ENTRYPOINT ["drupalstand-entrypoint"]

WORKDIR /var/www

RUN  apk add --no-cache \
             git \
             curl \
             vim \
             unzip \
             wget \
             ncurses \
             ncurses-terminfo \
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
  && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  # Remote connect_back only works on Linux systems because OSX abstracts it away into a VM
  && echo "xdebug.discover_client_host=true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
  && apk del .build-deps

# color avaiable thanks to ncurses packages above
ENV TERM xterm-256color

COPY docker-src/cms/php-conf.d.dev/* /usr/local/etc/php/conf.d/

WORKDIR /var/www/webroot

CMD ["php-fpm"]

#------------------------------------------------------------------------------
# Composer PROD
#------------------------------------------------------------------------------
FROM composer:latest as composer-prod

# Install tool to make Composer download packages in parallel
RUN  composer global require hirak/prestissimo \
  && mkdir -p /var/www/webroot/core \
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
FROM scratch as php-prod
COPY --from=php-base . /

COPY webroot /var/www/webroot
COPY config /var/www/config

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

WORKDIR /var/www/webroot

RUN ln -s /var/www/vendor/bin/* /bin/

#------------------------------------------------------------------------------
# WEB PROD
#------------------------------------------------------------------------------
FROM scratch as web-prod
COPY --from=web-dev . /

COPY --from=php-prod /var/www/webroot /var/www/webroot

WORKDIR /var/www/webroot
