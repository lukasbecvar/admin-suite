#!/bin/bash

# define colors
yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# start migration process
yellow_echo "[DB-Create]: starting migration process"

# run migrate commands
docker-compose run php bash -c "
    php bin/console doctrine:database:create --if-not-exists &&
    php bin/console doctrine:database:create --if-not-exists --env=test &&
    php bin/console doctrine:migrations:migrate --no-interaction &&
    php bin/console doctrine:migrations:migrate --no-interaction --env=test
"
