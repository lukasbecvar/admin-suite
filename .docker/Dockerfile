# define base php-apache image
FROM php:8.4-apache

# set container working directory
WORKDIR /var/www

# install required packages
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    iputils-ping \
    libicu-dev \
    libgmp-dev \
    libpng-dev \
    zlib1g-dev \
    curl \
    sudo \
    rpm

# install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    intl \
    curl \
    gmp

# install phpGD extension
RUN docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd

# install OPCache extension
RUN docker-php-ext-install opcache

# allow apache execute sudo commands without password
RUN echo 'www-data ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
