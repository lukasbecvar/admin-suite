#!/bin/bash

# check if environment is dev
[ -f .env ] && export $(grep -v '^#' .env | xargs)
[ "$APP_ENV" != "dev" ] && echo "This script is only for development environment" && exit 1

# drop databases
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:drop --env=test --force
