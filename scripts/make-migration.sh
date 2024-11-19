#!/bin/bash

# allow process only for development environment
[ -f .env ] && export $(grep -v '^#' .env | xargs)
[ "$APP_ENV" != "dev" ] && echo "This script is only for development environment" && exit 1

# generate migration file for update database structure to latest version
docker-compose run php php bin/console make:migration --no-interaction
