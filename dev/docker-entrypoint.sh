#!/bin/bash
set -e

for dir in _conf _logs _local _data; do
    mkdir -p "/var/www/html/$dir"
    chown www-data:www-data "/var/www/html/$dir"
    chmod 775 "/var/www/html/$dir"
done

chown -R www-data:www-data /var/www/html 2>/dev/null || true

exec "$@"
