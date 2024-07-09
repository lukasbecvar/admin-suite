#!/bin/bash

# refresh database
sh scripts/drop-database.sh
sh scripts/migrate.sh

# load testing data
php bin/console doctrine:fixtures:load --no-interaction
php bin/console doctrine:fixtures:load --no-interaction --env=test
