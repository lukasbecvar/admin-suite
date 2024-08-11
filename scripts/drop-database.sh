#!/bin/bash

# drop databases
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:drop --env=test --force
