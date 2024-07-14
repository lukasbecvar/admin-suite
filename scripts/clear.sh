#!/bin/bash

# clean app cache
php bin/console cache:clear

# delete migrations
rm -rf migrations/

# delete composer files
rm -rf vendor/
rm -rf composer.lock

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

# delete custom config files
rm -rf services.json
rm -rf package-requirements.json
