#!/bin/bash

# colors
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
RESET="\033[0m"

# install backend packages
if [ ! -d 'vendor/' ]
then
    docker-compose run composer install --ignore-platform-reqs
fi

# install frontend packages
if [ ! -d 'node_modules/' ]
then
    docker-compose run node npm install --loglevel=error
fi

# build frontend assets
if [ ! -d 'public/assets/' ]
then
    docker-compose run node npm run build
fi

# fix storage permissions
echo "${YELLOW}Setting permissions for var/ ...${RESET}"
if sudo chmod -R 777 var/ && sudo chown -R www-data:www-data var/; then
    echo "${GREEN}✔ Permissions successfully updated for var/${RESET}"
else
    echo "${RED}✘ Failed to update permissions for var/${RESET}"
fi
