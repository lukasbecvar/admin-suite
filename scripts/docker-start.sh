#!/bin/bash

# install app requirements
sh scripts/install.sh

# build docker containers
docker-compose up --build
