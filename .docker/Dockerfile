# use basic apache with php_mod image
FROM php:8.3-apache

# set default apache working directory
WORKDIR /var/www

# update packages and install dependencies and extension libraries
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libicu-dev \
    libgmp-dev \
    curl \
    sudo \
    rpm 

# install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    intl \
    curl \
    gmp

# install OPCache extension
RUN docker-php-ext-install opcache

# allow apache execute sudo commands without password
RUN echo 'www-data ALL=(ALL) NOPASSWD: ALL' >> /etc/sudoers
