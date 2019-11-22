#!/bin/bash

# Deploy the dev branch to the worpress-master branch
# to deploy the current dev version of autologin links

# Source: https://stackoverflow.com/questions/59895/get-the-source-directory-of-a-bash-script-from-within-the-script-itself?page=1&tab=votes#tab-top
SCRIPTDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

target="$1"
if [ -z "$target" ] ; then
    target=$SCRIPTDIR/../../trunk
fi

rsync -vr  --exclude-from=$SCRIPTDIR/deploy-exclude $SCRIPTDIR/ $target/
