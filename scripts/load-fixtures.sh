#!/bin/bash

#  drop database
sh scripts/drop-database.sh

# migrate database to latest version
sh scripts/migrate.sh

# load testing data fixtures
php bin/console doctrine:fixtures:load --no-interaction
php bin/console doctrine:fixtures:load --no-interaction --env=test
