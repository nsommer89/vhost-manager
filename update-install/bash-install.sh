#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
export TZ=Europe/Copenhagen

# docker run -p 80:80 -p 443:443 -p 22:22 -a stdin -a stdout -i -t ubuntu:22.04 /bin/bash
# Install instructions for bash
# Install as root
# ssh root@your.server
# apt-get update -y && apt-get install -y --no-install-recommends nano curl ca-certificates
# curl -O https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/bash-install.sh
# bash bash-install-full.sh (default nikolaj.jensen@gmail.com 123456)
# later on it will be possible choose which webserver and php version to install e.g ´bashbash-install-full.sh --webserver=nginx --php=8.1´

# The script should only be run as root
if [ "x$(id -u)" != 'x0' ]; then
    echo 'Error: this script can only be executed by root'
    exit 1
fi

# These values should match the newest stable version
WEBSERVER=nginx
DEFAULT_TIMEZONE=Europe/Copenhagen
LOCALE=en
WWW_ROOT=/var/www
VHOST_MANAGER_VERSION=1.0
VHOST_MANAGER_DIR=/opt/vhost-manager
GIT_REPO_ADDR=https://github.com/nsommer89/vhost-manager.git
VERSIONS_JSON_FILE_URL=https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/versions.json

echo "Do you want to install vhost manager OS depdencies?"
echo " - This option is helpful on a fresh install of Ubuntu 20.04|22.04 and will install all the dependencies needed for vhost manager to run."
echo " - Will install the php version you want, nginx, mariadb, composer, git, nodejs, npm, yarn, certbot, fail2ban, ufw"
echo " - PHP 8.1 and Git will be installed if not already installed, which is required for vhost manager to run."
echo " - apt repository ppa:ondrej/php will be added if not already added."
echo -n " - (y)/n: "
read INSTALL_OS_DEPENDENCIES
if [ "$INSTALL_OS_DEPENDENCIES" != "${INSTALL_OS_DEPENDENCIES#[Yy]}" ] ;then
    INSTALL_OS_DEPENDENCIES=true
else
    INSTALL_OS_DEPENDENCIES=false
fi
# Select how to install composer which is required for vhost manager to run
echo "Do you want to install composer in /usr/local/bin? (y)/n: "
echo "- If not composer will be installed in the vhost manager directory."
echo -n "(y)/n: "
read INSTALL_COMPOSER_GLOBALLY
if [ "$INSTALL_COMPOSER_GLOBALLY" != "${INSTALL_COMPOSER_GLOBALLY#[Yy]}" ] ;then
    INSTALL_COMPOSER_GLOBALLY=true
    COMPOSER_INSTALL_PATH=/usr/local/bin
else
    INSTALL_COMPOSER_GLOBALLY=false
    COMPOSER_INSTALL_PATH=/opt/composer-vhost-php8.1
    mkdir -p $COMPOSER_INSTALL_PATH
fi

# Select how mysql database should be setup
echo "Do you want let the installer automatically configure the mysql server and connection? (y)/n: "
echo "- If yes a local mysql server will be installed and configured with a root password and a vhostmanager_db database."
echo -n "(y)/n: "
read MYSQL_AUTO_CONFIGURE
if [ "$MYSQL_AUTO_CONFIGURE" != "${MYSQL_AUTO_CONFIGURE#[Nn]}" ] ;then
  MYSQL_AUTO_CONFIGURE=false
  # Configure vhost manager database connection
  echo -n "DB Host: "
  read MANUAL_DB_HOST
  if [ -z "$MANUAL_DB_HOST" ]; then
    echo "Invalid db host. Aborting."
    exit 1
  fi
  echo -n "DB User: "
  read MANUAL_DB_USER
  if [ -z "$MANUAL_DB_USER" ]; then
    echo "Invalid db user. Aborting."
    exit 1
  fi
  echo -n "DB Password: "
  read -r MANUAL_DB_PASS
  if [ -z "$MANUAL_DB_PASS" ]; then
    echo "Invalid db pass. Aborting."
    exit 1
  fi
  echo -n "DB database name: "
  read MANUAL_DB_DATABASE
  if [ -z "$MANUAL_DB_DATABASE" ]; then
    echo "Invalid database name. Aborting."
    exit 1
  fi
else
  MYSQL_AUTO_CONFIGURE=true
fi

if [ "$MYSQL_AUTO_CONFIGURE" = false ]; then
  echo -n "Do you want to import the vhost manager database structure? (y)/n: "
  read IMPORT_VHOST_MANAGER_DB_DUMP
  if [ "$IMPORT_VHOST_MANAGER_DB_DUMP" != "${IMPORT_VHOST_MANAGER_DB_DUMP#[Yy]}" ] ;then
      IMPORT_VHOST_MANAGER_DB_DUMP=true
  else
      IMPORT_VHOST_MANAGER_DB_DUMP=false
  fi
fi

if [ "$INSTALL_OS_DEPENDENCIES" = true ]; then
  # If the user does not provide php version, then we will use the latest stable version
  PHP_VERSION=8.1
  # Ask for PHP version
  echo "Available PHP versions: 7.4, 8.0, 8.1 (default), 8.2 or the version you want to install from ppa:ondrej/php"
  echo -n "What is the desired PHP version?: "
  read PHP_VERSION

  # Force the user to choose a PHP version
  if [ -z "$PHP_VERSION" ]; then
    echo "Invalid PHP version. Aborting."
    exit 1
  fi

  # Ask for certbot renewal email
  echo -n "Enter the email address you want to receive certbot renewal warnings: "
  read CERTBOT_EMAIL

  # Force the user to provide an email for certbot
  if [ -z "$CERTBOT_EMAIL" ]; then
    echo "Invalid CERTBOT_EMAIL. Aborting."
    exit 1
  fi

  # Set mysql root password
  echo -n "Set MySQL root password: "
  read -r MYSQL_ROOT_PASSWORD
  echo

  # Check if MySQL root password is set properly
  if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
    echo "Invalid MySQL root password. Aborting.";
    exit 1;
  fi

  enableUFW=true;
  # Ask if the user wants to enable the firewall and open ports 80 and 443
  echo "Do you want to install and enable UFW and open the ports 80 and 443? (y/n)? (default: yes) "
  read enableUFW

  # Let the user confirm the installation
  echo "Are you sure this is correct? (y/n)? "
  echo " - PHP Version: $PHP_VERSION"
  echo " - Certbot renewal email: $CERTBOT_EMAIL"
  if [ "$enableUFW" != "${enableUFW#[Yy]}" ] ;then
      echo " - UFW will be installed and ports 80 and 443 will be opened";
  else
      echo " - UFW will not be installed. No ports will be opened";
  fi

  read confirmInstall

  if [ "$confirmInstall" != "${confirmInstall#[Nn]}" ] ;then
      echo "Aborting installation";
      exit 1
  fi
fi

# Ensure the system is up to date wether we install OS dependencies or not
apt-get update -y && apt-get install -y --no-install-recommends \
  ca-certificates \
  mysql-client \
  curl \
  git \
  nano \
  tzdata

# Add ppa:ondrej/php repository
add-apt-repository ppa:ondrej/php -y

if [ "$INSTALL_OS_DEPENDENCIES" = true ]; then
  # Install software-properties-common to be able to use add-apt-repository
  apt-get install software-properties-common -y
  apt-get update -y

  # Update system and install packages and desired php version
  apt-get update -y && apt-get install -y --no-install-recommends software-properties-common apt-transport-https ca-certificates git apt-utils curl wget mysql-client sudo nginx openssl nano vim php$PHP_VERSION php$PHP_VERSION-cli php$PHP_VERSION-fpm php$PHP_VERSION-zip php$PHP_VERSION-mysql php$PHP_VERSION-curl php$PHP_VERSION-gd php$PHP_VERSION-mbstring php$PHP_VERSION-xml php$PHP_VERSION-xmlrpc php$PHP_VERSION-intl php$PHP_VERSION-readline php$PHP_VERSION-bcmath php$PHP_VERSION-imagick php$PHP_VERSION-redis php$PHP_VERSION-sqlite3 php$PHP_VERSION-pgsql mysql-server mysql-client

  if [ "$enableUFW" != "${enableUFW#[Yy]}" ] ;then
      apt-get install -y ufw
      ufw allow 80
      ufw allow 443
  fi

  # Install and configure certbot
  apt install certbot python3-certbot-nginx -y
  certbot register --non-interactive --agree-tos -m $CERTBOT_EMAIL
fi

# Install PHP8.1 which is used by vhost manager - if not already installed
if [ ! -f /usr/bin/php8.1 ]; then
    echo "Installing PHP 8.1"
    apt-get install -y php8.1 php8.1-cli php8.1-fpm php8.1-zip php8.1-mysql php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-xmlrpc php8.1-intl php8.1-readline php8.1-bcmath php8.1-imagick php8.1-redis php8.1-sqlite3 php8.1-pgsql
fi

if [ "$INSTALL_OS_DEPENDENCIES" = true ]; then
  # Update ca-certificates
  update-ca-certificates

  # Start MySQL and nginx
  service mysql start && service nginx start

  # Set the desired php version as default
  update-alternatives --set php /usr/bin/php$PHP_VERSION
  update-alternatives --set phar /usr/bin/phar$PHP_VERSION
  update-alternatives --set phar.phar /usr/bin/phar.phar$PHP_VERSION

  # Set MySQL root password
  mysql -u root <<-EOF
  UPDATE mysql.user SET Password=PASSWORD('$MYSQL_ROOT_PASSWORD') WHERE User='root';
  DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
  DELETE FROM mysql.user WHERE User='';
  DELETE FROM mysql.db WHERE Db='test' OR Db='test_%';
  FLUSH PRIVILEGES;
EOF
fi

if [ "$MYSQL_AUTO_CONFIGURE" = true ]; then
  # We need to save password in a variable to use it to connect application to database
  VHOST_MYSQL_PASS=$(< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c6)
  # Make database user for vhost-manager
  mysql -e "CREATE DATABASE vhostmanager_db;"
  # We need to save password in a variable to use it to connect application to database
  VHOST_MYSQL_PASS=$(< /dev/urandom tr -dc _A-Z-a-z-0-9 | head -c6)
  mysql -e "CREATE USER 'vhostmanager'@'localhost' IDENTIFIED WITH mysql_native_password BY '$VHOST_MYSQL_PASS';"
  mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, DROP, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES ON vhostmanager_db.* TO 'vhostmanager'@'localhost';"
  # Grant this user vhostmanager the FILE global privilege: (if enabled, reports will be archived faster thanks to the LOAD DATA INFILE feature)
  mysql -e "GRANT FILE ON *.* TO 'vhostmanager'@'localhost';"
fi

# Make php directory
mkdir -p /run/php

# Install Composer
export COMPOSER_ALLOW_SUPERUSER=1
curl -sS https://getcomposer.org/installer | php -- --install-dir=$COMPOSER_INSTALL_PATH --filename=composer

# Create vhost-manager directory
mkdir -p $VHOST_MANAGER_DIR

# Clone the repository 
git clone $GIT_REPO_ADDR $VHOST_MANAGER_DIR && cd $VHOST_MANAGER_DIR && git checkout master && cd /

if [ "$MYSQL_AUTO_CONFIGURE" = true ]; then
  # Setup vhost-manager SQL database structure
  echo "Importing vhost-manager database dump"
  mysql vhostmanager_db < $VHOST_MANAGER_DIR/update-install/dump.sql
fi
if [ "$MYSQL_AUTO_CONFIGURE" = false ]; then
  if [ "$IMPORT_VHOST_MANAGER_DB_DUMP" = true ]; then
    echo "Importing vhost-manager database dump"
    mysql -u$MANUAL_DB_USER -p$MANUAL_DB_PASS $MANUAL_DB_DATABASE -h$MANUAL_DB_HOST < $VHOST_MANAGER_DIR/update-install/dump.sql
  fi
fi

# Run composer install with php8.1 in vhost-manager app directory
cd $VHOST_MANAGER_DIR/app && /usr/bin/php8.1 $COMPOSER_INSTALL_PATH/composer install && cd /

# Symbolic link the vhost-manager
ln -s $VHOST_MANAGER_DIR/app/bin/console /usr/local/bin/vhost && chmod a+rx /usr/local/bin/vhost

# Make vhost-admin group
addgroup vhost-admin

# set permissions
chown -R root:vhost-admin $VHOST_MANAGER_DIR
chmod -R 755 $VHOST_MANAGER_DIR

# Make vhost-manager executable for vhost-admin group
echo "vhost-admin     ALL = (root) NOPASSWD: /usr/local/bin/vhost" >> /etc/sudoers

# Git add global safe directory
git config --global --add safe.directory $VHOST_MANAGER_DIR

# Let users automatically run vhost-manager with sudo
echo "alias vhost=\"sudo vhost\"" >> /etc/bash.bashrc

if [ "$MYSQL_AUTO_CONFIGURE" = true ]; then
  MYSQL_HOSTNAME=localhost
  MYSQL_DATABASE=vhostmanager_db
  MYSQL_USER=vhostmanager
  MYSQL_PASSWORD="$VHOST_MYSQL_PASS"
else
  MYSQL_HOSTNAME=$MANUAL_DB_HOST
  MYSQL_DATABASE=$MANUAL_DB_DATABASE
  MYSQL_USER=$MANUAL_DB_USER
  MYSQL_PASSWORD=$MANUAL_DB_PASS
fi

# Add environment variables
cat > $VHOST_MANAGER_DIR/app/.env <<EOF
COMPOSER_INSTALL_PATH=$COMPOSER_INSTALL_PATH
VERSIONS_JSON_FILE_URL=$VERSIONS_JSON_FILE_URL
VHOST_MANAGER_VERSION=$VHOST_MANAGER_VERSION
VHOST_MANAGER_DIR=$VHOST_MANAGER_DIR
DB_CONNECTION=mysql
MYSQL_HOSTNAME=$MYSQL_HOSTNAME
MYSQL_DATABASE=$MYSQL_DATABASE
MYSQL_USER=$MYSQL_USER
MYSQL_PASSWORD=$MYSQL_PASSWORD
WWW_ROOT=$WWW_ROOT
PHP_VERSION=$PHP_VERSION
WEBSERVER=$WEBSERVER
DEFAULT_TIMEZONE=$DEFAULT_TIMEZONE
LOCALE=$LOCALE
APP_SYSTEM_PATH=$VHOST_MANAGER_DIR/app
EOF

cat $VHOST_MANAGER_DIR/app/bin/brand
echo ""
echo "Vhost-manager installed successfully!"
echo "You can now run vhost command to manage your virtual hosts."

exit 0