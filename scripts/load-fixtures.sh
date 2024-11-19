#!/bin/bash

# allow process only for development environment
[ -f .env ] && export $(grep -v '^#' .env | xargs)
[ "$APP_ENV" != "dev" ] && echo "This script is only for development environment" && exit 1

# drop database and migrate for recreating database
sh scripts/drop-database.sh
sh scripts/migrate.sh

# load testing data
docker-compose run php bash -c "
    php bin/console doctrine:fixtures:load --no-interaction &&
    php bin/console doctrine:fixtures:load --no-interaction --env=test
"
