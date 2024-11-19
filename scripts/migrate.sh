#!/bin/bash

# run database create and migration
docker-compose run php bash -c "
    php bin/console doctrine:database:create --if-not-exists &&
    php bin/console doctrine:database:create --if-not-exists --env=test &&
    php bin/console doctrine:migrations:migrate --no-interaction &&
    php bin/console doctrine:migrations:migrate --no-interaction --env=test
"
