#!/bin/sh

set -o xtrace
set -x

if [ ! $(getent group 1000) ]; then
  addgroup -g 1000 -S user
fi

GROUP_NAME=$(getent group 1000 | cut -d: -f1)

if [ ! $(getent passwd 1000) ]; then
  adduser -S -D -u 1000 -G $GROUP_NAME user
fi
