#!/bin/bash

yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# clear console
clear

# load testing data fixtures
sh scripts/load-fixtures.sh

# run phpcs process
yellow_echo 'PHPCS: testing...'
php bin/phpcbf
php bin/phpcs

# analyze phpstan
yellow_echo 'PHPSTAN: testing...'
php bin/phpstan analyze

# PHPUnit tests
yellow_echo 'PHPUnit: testing...'
php bin/phpunit
