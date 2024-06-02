#!/bin/bash

# drop database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:drop --env=test --force
