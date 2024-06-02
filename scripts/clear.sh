#!/bin/bash

# clean app & cache
php bin/console cache:clear

# delete old migrations
rm -rf migrations/

# delete composer files
rm -rf composer.lock
rm -rf vendor/

# delete npm packages
rm -rf node_modules/
rm -rf package-lock.json

# delete builded assets
rm -rf public/build/
rm -rf public/bundles/

# delete phpdoc cache files
rm -rf .phpdoc
rm -rf .phpcs-cache

# delete symfony cache folder
sudo rm -rf var/

# delete docker services data
sudo rm -rf _docker/services/
