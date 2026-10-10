#!/bin/sh
set -e

php-fpm -D
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
