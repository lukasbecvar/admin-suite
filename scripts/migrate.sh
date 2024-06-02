#!/bin/bash

yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# delete old migrations
rm -rf migrations

# create migrations dir
mkdir migrations

# create database
yellow_echo "[DB-Create]: creating databases"
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:database:create --if-not-exists --env=test

# database migration for update database structure
yellow_echo "[DB-Create]: running migrations"
php bin/console make:migration --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --env=test
