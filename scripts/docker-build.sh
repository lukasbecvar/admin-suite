#!/bin/bash

# install & build assets
sh scripts/install.sh

# start npm watch in background
npm run dev

# build docker containers
docker-compose up --build
