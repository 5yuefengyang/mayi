#!/bin/bash
/etc/init.d/php-fpm start
/usr/local/nginx/sbin/nginx -t
exec "$@"
