#!/bin/bash

# define colors
yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# clear console output
clear

yellow_echo "Starting tests..."

# run tests
docker-compose run --no-deps php bash -c "
    php bin/console doctrine:database:drop --env=test --force &&
    php bin/console doctrine:database:create --if-not-exists --env=test &&
    php bin/console doctrine:migrations:migrate --no-interaction --env=test &&
    php bin/console doctrine:fixtures:load --no-interaction --env=test &&
    php vendor/bin/phpcbf &&
    php vendor/bin/phpcs &&
    php vendor/bin/phpstan analyze &&
    php bin/phpunit 2>/dev/null
"
