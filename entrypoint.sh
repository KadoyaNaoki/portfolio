#!/bin/bash
cd /var/www/html

# Apache が勝手に起動していたら停止（Render 対策）
apachectl stop 2>/dev/null || true

# storage/logs を強制作成（root が作る前に）
mkdir -p storage/logs storage/framework/sessions storage/framework/cache storage/framework/views

# 権限付与
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache storage/logs storage/framework

# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# マイグレーション
php artisan migrate --force || true

# Apache をここで初めて起動
apache2-foreground
