#!/bin/bash

yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# create databases
yellow_echo "[DB-Create]: creating databases"
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:database:create --if-not-exists --env=test

# database migration for update database structure to latest version
yellow_echo "[DB-Create]: running migrations"
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --env=test
