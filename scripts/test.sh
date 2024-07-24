#!/bin/bash

yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# clear console
clear

# load testing data fixtures
php bin/console doctrine:database:drop --env=test --force
php bin/console doctrine:database:create --if-not-exists --env=test
php bin/console doctrine:migrations:migrate --no-interaction --env=test
php bin/console doctrine:fixtures:load --no-interaction --env=test

# run phpcs process
yellow_echo 'PHPCS: testing...'
php bin/phpcbf
php bin/phpcs

# analyze phpstan
yellow_echo 'PHPSTAN: testing...'
php bin/phpstan analyze -vvv

# PHPUnit tests
yellow_echo 'PHPUnit: testing...'
php bin/phpunit
