FROM ubuntu:19.10

# Libraries
ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN apt-get update && apt-get install -my \
  bsdmainutils \
  curl \
  git \
  gnupg2 \
  make \
  nodejs \
  php-apcu \
  php-cli \
  php-curl \
  php-fpm \
  php-gd \
  php-iconv \
  php-intl \
  php-json \
  php-mbstring \
  php-mysqlnd \
  php-opcache \
  php-xml \
  php-zip \
  sudo \
  wget \
  zip

RUN mkdir /run/php

# PHP-FPM
ADD www.conf /etc/php/7.3/fpm/pool.d/www.conf
ADD php-fpm.conf /etc/php/7.3/fpm/php-fpm.conf

# Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# Yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
RUN sudo apt-get update && sudo apt-get install yarn

# Startup command
ADD startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

WORKDIR /var/www/symfony
EXPOSE 9000

#CMD tail -f /dev/null
CMD ["/usr/local/bin/startup.sh"]
