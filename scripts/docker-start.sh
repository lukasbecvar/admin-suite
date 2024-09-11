#!/bin/bash

# install app requirements
sh scripts/install.sh

# start npm watch in background (assets builder)
npm run watch &

# build docker containers
docker-compose up --build
