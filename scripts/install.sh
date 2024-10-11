#!/bin/bash

# install composer packages
if [ ! -d 'vendor/' ]
then
    docker-compose run composer
fi

# install node-modules frontend packages
if [ ! -d 'node_modules/' ]
then
    docker-compose run node npm install --loglevel=error
fi

# build assets
if [ ! -d 'public/assets/' ]
then
    docker-compose run node npm run build
fi

# fix storage permissions
sudo chmod -R 777 var/
sudo chown -R www-data:www-data var/
