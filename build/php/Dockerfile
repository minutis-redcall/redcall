FROM ubuntu:17.10

# Libraries
RUN apt-get update && apt-get install -my \
  sudo \
  curl \
  wget \
  php-curl \
  php-fpm \
  php-gd \
  php-xsl \
  php-mysqlnd \
  php-mcrypt \
  php-cli \
  php-intl \
  php-bz2 \
  php-zip \
  php-mbstring \
  git \
  zip \
  php-apcu \
  php-redis \
  php-opcache \
  make \
  nodejs

RUN mkdir /run/php

# PHP-FPM
ADD www.conf /etc/php/7.1/fpm/pool.d/www.conf
ADD php-fpm.conf /etc/php/7.1/fpm/php-fpm.conf

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

CMD ["/usr/local/bin/startup.sh"]