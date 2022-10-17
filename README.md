# vhost-manager for Ubuntu
#### vhost-manager is a tool for ubuntu servers to easily manage shared web hosting sites with nginx and php-fpm
### Demo/Screenshot
<img src="https://raw.githubusercontent.com/nsommer89/vhost-manager/master/screenshot.png" width="350">

## Requirements:
#### Have a fresh installation of one the following Ubuntu versions: 20.04, 22.04 on a server or use docker!

## Important Notices
#### You can choose to use vhost-manager as super root, but if you want to grant a user access, you'll have to add them to `sudo` group and `vhost-admin` group
#### When installing vhost manager it's required to do it as super root!

## Installation on a shared hosting server
#### Have a fresh installation of one the following Ubuntu versions: 20.04, 22.04

Remember when running the bash-install script to be sudo root, and also as mentioned use a fresh server!
1. Update and install utillities: `apt-get update -y && apt-get install -y --no-install-recommends nano curl ca-certificates`
2. Download vhost-manager installation script: `curl -O https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/bash-install-full.sh`
3. Install it: `bash bash-install-full.sh`
4. Try it out `vhost` or `vhost <some-command>`
-----
## Run in a docker Ubuntu container (for testing purpose)

1. Start a ubuntu docker container: `docker run -p 80:80 -p 443:443 -p 22:22 -a stdin -a stdout -i -t ubuntu:22.04 /bin/bash`
2. When inside the container run `apt-get update -y && apt-get install -y --no-install-recommends nano curl ca-certificates`
3. Download vhost-manager installation script: `curl -O https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/bash-install-full.sh`
4. Install it: `bash bash-install-full.sh`

## Run in Docker Compose
1. Copy the env.dist file `cp app/.env.dist app/.env` and fill out the values
2. Run `docker-compose build` to build it
3. Run `docker-compose up` - You can use `./cli` to shell into the container or `./cli <your-command>`
4. Dump the contents from `update-install/dump.sql` to the database which name you can see in the .env file. You can find phpMyAdmin at http:/localhost:8018/
5. Try it out with `./cli` or you can also do `./cli vhost <some-command> <some-argument>` 

## Run in an already existing environment on Ubuntu >=20
#### Stand-alone installation script is coming up soon

## Test Domains / DNS
#### To test it, you can add a DNS A record that points to 127.0.0.1, e.g. *.yourdomain.com and use this to create sites in the vhost-manager.
#### Eventually, you can use *.dev.cloudtek.dk which points to 127.0.0.1
-----
Version history, see https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/versions.json
-----
## Todo
No items currently

## Update version checklist:
1. Update and render it in GenerateVersions.php
2. Update VHOST_MANAGER_VERSION variable in install script to install newest version
3. Push to develop
4. Merge develop into master
5. Branch new version branch `v<xxx>` from master