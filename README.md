# vhost-manager for Ubuntu

#### Demo/Screenshot
<img src="https://raw.githubusercontent.com/nsommer89/vhost-manager/master/screenshot.png" width="350">

#### ABOUT: vhost-manager is a tool for ubuntu servers to easily manage shared web hosting sites with nginx and php-fpm

## Installation on a shared hosting server

#### Have a fresh installation of one the following Ubuntu versions: 20.04, 22.04

#### When installing vhost manager it's required to do it as super root!

#### You can choose to use vhost-manager as super root, but if you want to grant a user access, you'll have to add them to `sudo` group and `vhost-admin` group

1. Update and install utillities: `apt-get update -y && apt-get install -y --no-install-recommends nano curl ca-certificates`
2. Download vhost-manager installation script: `curl -O https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/bash-install-full.sh`
3. Install it: `bash bash-install-full.sh`
-----
## Run in a docker Ubuntu container (for testing purpose)

1. Start a ubuntu docker container: `docker run -p 80:80 -p 443:443 -p 22:22 -a stdin -a stdout -i -t ubuntu:22.04 /bin/bash`
2. When inside the container run `apt-get update -y && apt-get install -y --no-install-recommends nano curl ca-certificates`
3. Download vhost-manager installation script: `curl -O https://raw.githubusercontent.com/nsommer89/vhost-manager/master/update-install/bash-install-full.sh`
4. Install it: `bash bash-install-full.sh`

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