#!/bin/bash

# Laravel の書き込み権限
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# キャッシュクリア
php artisan view:clear
php artisan config:clear
php artisan route:clear

# マイグレーション（失敗しても継続）
php artisan migrate --force || true

# Apache 起動
apache2-foreground
