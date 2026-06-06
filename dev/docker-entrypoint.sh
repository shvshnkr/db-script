#!/bin/bash
set -e

for dir in _conf _logs _local _data; do
    mkdir -p "/var/www/html/$dir"
    chown www-data:www-data "/var/www/html/$dir"
    chmod 775 "/var/www/html/$dir"
done

chown -R www-data:www-data /var/www/html 2>/dev/null || true

if [[ -f /var/www/html/composer.json ]]; then
    if [[ ! -x /var/www/html/vendor/bin/phpunit ]]; then
        echo "docker-entrypoint: composer install (dev test deps)..."
        composer install --no-interaction --working-dir=/var/www/html || \
            echo "WARN: composer install failed (phpunit may be skipped)"
    fi
fi

# arch-modern: DBAL needs pdo_mysql (mysqli alone is legacy dbscore.lib)
if ! php -m 2>/dev/null | grep -q '^pdo_mysql$'; then
    echo "docker-entrypoint: enabling pdo_mysql for arch-modern/DBAL..."
    docker-php-ext-install pdo_mysql >/dev/null 2>&1 || echo "WARN: pdo_mysql install failed"
fi

exec "$@"
