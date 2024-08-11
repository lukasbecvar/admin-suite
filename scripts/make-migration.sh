#!/bin/bash

# generate migration file for database update structure to latest version
php bin/console make:migration --no-interaction
