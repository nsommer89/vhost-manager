#!/bin/bash

VHOST_MANAGER_DIR=/opt/vhost-manager
VHOST_MANAGER_VERSION=$1

# Git pull
cd $VHOST_MANAGER_DIR && git pull &> /dev/null && cd /
echo "Git pull... Done!"

# Run composer install with php8.1 in vhost-manager app directory
cd $VHOST_MANAGER_DIR/app && /usr/bin/php8.1 /usr/local/bin/composer install && cd /
echo "Composer install... Done!"

# Run migration if any
if [ -s $VHOST_MANAGER_DIR/update-install/migrate.sql ]
then
     mysql vhostmanager_db < $VHOST_MANAGER_DIR/update-install/migrate.sql
fi

# Change the version in the environment file
sed -i "s/VHOST_MANAGER_VERSION=.*/VHOST_MANAGER_VERSION=$VHOST_MANAGER_VERSION/g" $VHOST_MANAGER_DIR/app/.env

# restart php8.1-fpm
systemctl restart php8.1-fpm

exit