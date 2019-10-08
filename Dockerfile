FROM php:7.3.7

RUN apt-get update

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# zip extension
RUN apt-get install -y \
        wget \
        libzip-dev \
        zip \
        unzip \
        libsodium-dev \
  && docker-php-ext-configure zip --with-libzip \
  && docker-php-ext-install zip \
  && docker-php-ext-install sodium \
  && docker-php-ext-install sockets

# ensure chromium browser installed
RUN apt-get install chromium -y

RUN usermod -u 1000 www-data
WORKDIR /var/www/app

# Install dependencies
RUN chown www-data:www-data -R /var/www
USER www-data
COPY --chown=www-data:www-data composer.json composer.json
COPY --chown=www-data:www-data composer.lock composer.lock

RUN composer install --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data . /var/www/app

USER www-data
RUN composer dump-autoload --no-scripts --optimize

CMD tail -f /dev/null
