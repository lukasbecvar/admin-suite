#!/bin/bash

# colors
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[1;33m"
RESET="\033[0m"

# function to remove file/dir
remove_item() {
    local target=$1

    # check if target exists
    if [ -e "$target" ]; then
        echo "${YELLOW}Removing $target...${RESET}"
        sudo rm -rf "$target"
        # check if target removed
        if [ ! -e "$target" ]; then
            echo "${GREEN}✔ Successfully removed $target${RESET}"
        else
            echo "${RED}✘ Failed to remove $target${RESET}"
        fi
    else
        echo "${GREEN}✔ $target not found, nothing to remove${RESET}"
    fi
}

remove_item "composer.lock"
remove_item "vendor/"
remove_item "package-lock.json"
remove_item "node_modules/"
remove_item "public/bundles/"
remove_item "public/assets/"
remove_item "var/"
remove_item ".docker/services/"
