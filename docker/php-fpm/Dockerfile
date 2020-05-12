FROM php:7-fpm
WORKDIR "/symbiota"

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get -y --no-install-recommends install \
        git \
        imagemagick \
        libjpeg-dev \
        libpng-dev \
        libpq-dev \
        libyaml-dev \
        libzip-dev \
        unzip \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /var/cache/apt/*

RUN docker-php-ext-install \
        gd \
        mysqli \
        pgsql \
        zip \
    && pecl install redis yaml \
    && docker-php-ext-enable \
        redis \
        yaml

RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

RUN php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');" && \
    php /tmp/composer-setup.php --install-dir=/usr/bin --filename=composer && \
    php -r "unlink('/tmp/composer-setup.php');"
