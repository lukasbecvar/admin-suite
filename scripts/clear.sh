#!/bin/bash

# delete composer files
sudo rm -rf composer.lock
sudo rm -rf vendor/

# delete npm packages
sudo rm -rf package-lock.json
sudo rm -rf node_modules/

# delete builded assets
sudo rm -rf public/bundles/
sudo rm -rf public/assets/

# delete symfony cache folder
sudo rm -rf var/

# delete docker services data
sudo rm -rf .docker/services/
