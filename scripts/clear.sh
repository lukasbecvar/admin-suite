#!/bin/bash

# clean app cache
php bin/console cache:clear

# delete composer files
rm -rf composer.lock
rm -rf vendor/

# delete npm packages
rm -rf package-lock.json
rm -rf node_modules/

# delete builded assets
rm -rf public/bundles/
rm -rf public/assets/

# delete symfony cache folder
sudo rm -rf var/

# delete docker services data
sudo rm -rf _docker/services/
