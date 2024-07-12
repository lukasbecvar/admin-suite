# use basic apache with php_mod image
FROM php:8.3-apache

# set default apache working directory
WORKDIR /var/www

# copy project files to container
# COPY . /var/www/

# update packages and install dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \ 
    unzip \
    zip \
    rpm 
    
# install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo intl

# install OPCache
RUN docker-php-ext-install opcache

# install APCu
RUN pecl install apcu \
    && docker-php-ext-enable apcu

# enable apache rewrite module
RUN a2enmod rewrite
