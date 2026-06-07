FROM php:8.4-fpm-alpine AS base

RUN apk --update --no-cache add \
      bash git unzip vim wget make g++ gcc \
      icu-dev icu-data-full \
      oniguruma-dev libzip-dev \
      freetype freetype-dev \
      libpng libpng-dev \
      libjpeg-turbo libjpeg-turbo-dev \
      libwebp libwebp-dev \
      libsodium-dev libxml2-dev \
      gmp-dev

RUN docker-php-ext-install opcache
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install intl
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install zip
RUN docker-php-ext-install sodium
RUN docker-php-ext-install dom
RUN docker-php-ext-configure pcntl --enable-pcntl && docker-php-ext-install pcntl
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && docker-php-ext-install gd
RUN docker-php-ext-install gmp

RUN cd /usr/local/etc/php/conf.d/ \
  && echo 'memory_limit = 2G' >> app.ini \
  && echo 'post_max_size = 20M' >> app.ini \
  && echo 'upload_max_filesize = 20M' >> app.ini \
  && echo 'max_execution_time = 120' >> app.ini \
  && echo 'output_buffering = 4096' >> app.ini \
  && echo 'date.timezone = Europe/Paris' >> app.ini \
  && echo 'display_errors = On' >> app.ini \
  && echo 'error_reporting = E_ALL' >> app.ini

RUN cd /usr/local/etc/php-fpm.d/ \
  && echo 'pm = dynamic' >> www.conf \
  && echo 'pm.max_children = 50' >> www.conf \
  && echo 'pm.start_servers = 5' >> www.conf \
  && echo 'pm.min_spare_servers = 3' >> www.conf \
  && echo 'pm.max_spare_servers = 10' >> www.conf \
  && echo 'pm.status_path = /status' >> www.conf

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

ENV COMPOSER_HOME=/tmp/composer
RUN mkdir -p /tmp/composer && chown -R 1000:1000 /tmp/composer /var/www

USER 1000
WORKDIR /var/www/symfony

ENTRYPOINT ["php-fpm"]
