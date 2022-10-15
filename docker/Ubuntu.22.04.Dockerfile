FROM ubuntu:22.04

ARG DEBIAN_FRONTEND=noninteractive
ARG CERTBOT_EMAIL="nikolaj.jensen@gmail.com"
ARG VHOST_MANAGER_DIR="/opt/vhost-manager"

USER root

# Copy App
COPY ./ ${VHOST_MANAGER_DIR}

# Data Directories
RUN mkdir -p /data && \
    mkdir -p /data/etc/sites-available && \
    mkdir -p /data/etc/sites-enabled && \
    mkdir -p /data/etc/php8.1-fpm && \
    # Create folder for php
    mkdir -p /run/php && \
    # Menu
    ln -s ${VHOST_MANAGER_DIR}/app/bin/console /usr/local/bin/vhost && \
    chmod a+rx /usr/local/bin/vhost && \
    # Install
    apt-get update -y && apt-get install -y --no-install-recommends \
    # Certbot
    certbot \
    python3-certbot-nginx \
    # Certificate Authorities
    ca-certificates \
    # UFW Firewall
    ufw \
    # Utils
    git \
    apt-utils \
    curl \
    wget \
    mysql-client \
    sudo \
    sqlite3 \
    openssh-server \
    # Install nginx
    nginx \
    # Install php8.1-fpm
    php8.1 \
    php8.1-cli \
    php8.1-fpm \
    # PHP extensions
    php8.1-zip \
    php8.1-mysql \
    php8.1-curl \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-xmlrpc \
    php8.1-intl \
    php8.1-readline \
    php8.1-bcmath \
    php8.1-imagick \
    php8.1-redis \
    php8.1-sqlite3 \
    # Install tools
    openssl \
    nano && \
    # Clean up
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    certbot register --non-interactive --agree-tos -m ${CERTBOT_EMAIL} && \
    update-ca-certificates && \
    # Make permissions
    addgroup vhost-admin && \
    chown -R root:vhost-admin ${VHOST_MANAGER_DIR} && \
    chmod -R 755 ${VHOST_MANAGER_DIR} && \
    echo "vhost-admin     ALL = (root) NOPASSWD: /usr/local/bin/vhost" >> /etc/sudoers && \
    echo "alias vhost=\"sudo vhost\"" >> /etc/bash.bashrc

# Add golang version of supervisor
COPY --from=docker.io/ochinchina/supervisord:latest /usr/local/bin/supervisord /usr/local/bin/supervisord
COPY ./docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Open ports
EXPOSE 80 443

# Start supervisor
CMD [ "/usr/local/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf" ]
