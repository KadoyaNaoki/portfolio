#!/bin/bash

# 最初に Laravel ディレクトリへ移動
cd /var/www/html

# storage/logs を強制的に作成（root が作る前に）
mkdir -p storage/logs
mkdir -p storage/framework/sessions storage/framework/cache storage/framework/views

# 起動直後に権限付与
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache storage/logs storage/framework

# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# マイグレーション
php artisan migrate --force || true

# Apache 起動
apache2-foreground