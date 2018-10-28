#!/bin/bash

set -e

echo "Running install routine..."
curl -X POST -d language= http://localhost:8888/wp-admin/install.php?step=1 > /dev/null

curl -X POST \
    -d admin_email=wordpress@localhost.local \
    -d admin_password=wordpress \
    -d admin_password2=wordpress \
    -d blog_public=0 \
    -d language= \
    -d pass1-text=wordpress \
    -d pw_weak=on \
    -d Submit=Install+WordPress \
    -d user_name=wordpress \
    -d weblog_title=wordpress \
        http://localhost:8888/wp-admin/install.php?step=2 > /dev/null

echo "Installing the plugin..."
docker cp ../ testing_wordpress_1:/var/www/html/wp-content/plugins/autologin-links

