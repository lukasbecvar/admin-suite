#!/bin/bash

# allow process only for development environment
[ -f .env ] && export $(grep -v '^#' .env | xargs)
[ "$APP_ENV" != "dev" ] && echo "This script is only for development environment" && exit 1

# drop databases
docker-compose run php bash -c "
    php bin/console doctrine:database:drop --force &&
    php bin/console doctrine:database:drop --env=test --force
"
