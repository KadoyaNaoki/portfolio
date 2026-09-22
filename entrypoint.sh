#!/bin/bash

echo "Running entrypoint.sh..." >> /var/log/entrypoint.log
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache storage/logs storage/framework
echo "Permission fixed." >> /var/log/entrypoint.log

# キャッシュクリア
php artisan view:clear || true
php artisan config:clear || true
php artisan route:clear || true

# マイグレーション（失敗しても継続）
php artisan migrate --force || true

# Apache 起動
apache2-foreground
