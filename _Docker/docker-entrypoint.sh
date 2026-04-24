#!/bin/sh
set -e
# Удаляем закэшированный список пакетов, чтобы Laravel пересобрал его из vendor.
# Иначе в томе может остаться старый cache с dev-пакетами (например Pail), которых нет в --no-dev образе.
CACHE_DIR="/var/www/wix/todo/bootstrap/cache"
rm -f "${CACHE_DIR}/packages.php" "${CACHE_DIR}/services.php"

if [ "${DEV_MOUNT:-}" = "1" ]; then
    cd /var/www/wix/todo
    _need_composer=0
    if [ ! -f vendor/autoload.php ]; then
        _need_composer=1
    elif [ "${1:-}" = "php-fpm" ]; then
        _need_composer=1
    fi
    if [ "$_need_composer" = "1" ]; then
        composer install --no-interaction --prefer-dist
        if [ "${FORCE_FRONTEND_BUILD:-0}" = "1" ]; then
            npm ci
            npm run build
        elif [ ! -f public/build/manifest.json ] && [ ! -f public/build/.vite/manifest.json ]; then
            npm ci
            npm run build
        fi
        chown -R www-data:www-data storage bootstrap/cache public/build vendor 2>/dev/null || true
        chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
    fi
fi

exec "$@"
