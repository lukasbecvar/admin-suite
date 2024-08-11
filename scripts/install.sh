#!/bin/bash

# install composer packages
if [ ! -d 'vendor/' ]
then
    composer install
fi

# install node-modules frontend packages
if [ ! -d 'node_modules/' ]
then
    npm install --loglevel=error
fi

# build assets
if [ ! -d 'public/assets/' ]
then
    npm run build
fi

# fix storage permissions
sudo chmod -R 777 var/
sudo chown -R www-data:www-data var/
