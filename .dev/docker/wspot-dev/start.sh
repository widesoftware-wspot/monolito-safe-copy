#!/bin/bash

php-fpm5.6 --pid /var/run/php-fpm.pid -y /etc/php/5.6/fpm/pool.d/wspot.com.br.conf

nginx -g 'daemon off;' -c /etc/nginx/nginx.conf