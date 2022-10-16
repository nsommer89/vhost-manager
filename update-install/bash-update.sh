#!/bin/bash

VHOST_MANAGER_VERSION=$1
VHOST_MANAGER_DIR=$2

if [ -z "$VHOST_MANAGER_DIR" ]; then
  echo "Invalid VHOST_MANAGER_DIR. Aborting."
  exit 1
fi

if [ -z "$VHOST_MANAGER_VERSION" ]; then
  echo "Invalid VHOST_MANAGER_VERSION. Aborting."
  exit 1
fi

# Git pull
cd $VHOST_MANAGER_DIR && git pull &> /dev/null && cd /
echo "Git pull... Done!"

# Run composer install with php8.1 in vhost-manager app directory
cd $VHOST_MANAGER_DIR/app && /usr/bin/php8.1 /usr/local/bin/composer install && cd /
echo "Composer install... Done!"

# Migrations
MIGRATIONS_DIR=$VHOST_MANAGER_DIR/update-install/migrations
MIGRATIONS="$MIGRATIONS_DIR/*.sql"

if [ -n "$(ls -A $MIGRATIONS_DIR 2>/dev/null)" ]
then
    echo "Running migrations..."
    for m in $MIGRATIONS
    do
        if [ -f "$m" ]
        then
            echo "Processing $m migration..."
            mysql vhostmanager_db < $m
        else
            echo "Warning: Some problem with migration \"$m\""
        fi
    done
    echo "Migrations done!"
fi

# Change the version in the environment file
sed -i "s/VHOST_MANAGER_VERSION=.*/VHOST_MANAGER_VERSION=$VHOST_MANAGER_VERSION/g" $VHOST_MANAGER_DIR/app/.env

# restart php8.1-fpm
systemctl restart php8.1-fpm

exit