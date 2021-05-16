#!/bin/bash

prefix=$( tr -d '.' <<< ${prefix:-testing} )

retry_count=60

echo "Running install routine..."
while [ $retry_count -gt 0 ] ; do
  sleep 1
  retry_count=$[ $retry_count - 1 ]
  echo "Retrying ($retry_count)..."
  if curl -s http://localhost/wp-admin/install.php?step=1 > /tmp/auto-install.tmp; then
    if grep "Error establishing a database connection" "/tmp/auto-install.tmp" > /dev/null ; then
      echo ".. db is down"
    else
      break
    fi
  fi
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
    -d Submit="Install+WordPress" \
    -d language= \
        http://localhost/wp-admin/install.php?step=2 > /dev/null
