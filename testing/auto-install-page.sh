#!/bin/bash

prefix=$( tr -d '.' <<< ${prefix:-testing} )

retry_count=20

echo "Running install routine..."
while [ $retry_count -gt 0 ] ; do
  if curl -s -X POST -d language= http://localhost:8888/wp-admin/install.php?step=1 > /dev/null ; then
    break
  fi
  retry_count=$[ $retry_count - 1 ]
  echo "Retrying ($retry_count)..."
  sleep 1
done
if [ $retry_count -le 0 ] ; then
  echo "Failed to setup website"
  exit 1
fi

set -e
curl -s -X POST \
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
