#!/bin/bash

set -e

systemctl stop docker &&  mount -t tmpfs tmpfs /var/lib/docker -o size=16g,gid=0,uid=0 && systemctl start docker

echo Press Ctrl+D to detach ramdisk
cat

systemctl stop docker &&  umount /var/lib/docker  && systemctl start docker
