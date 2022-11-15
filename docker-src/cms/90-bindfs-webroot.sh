#!/bin/sh
 bindfs --force-user=nginx --force-group=nginx --create-for-user=1000 --create-for-group=1000 --chown-ignore --chgrp-ignore /var/www/webroot-host /var/www/webroot
