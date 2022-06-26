#!/bin/sh

# Absolute path to this script, e.g. /home/user/bin/foo.sh
SCRIPT=$(readlink -f "$0")

# App directory
APP_DIR=$(readlink -f $(dirname "$SCRIPT")/..)
cd $APP_DIR

vendor/bin/phpmd src text phpmd.xml
vendor/bin/phpcs -p
vendor/bin/psalm
