#!/bin/bash

# storage ディレクトリを確実に作成
mkdir -p storage/logs
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache
mkdir -p storage/framework/views

# 権限付与（Render ではこれが必須）
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache storage/logs storage/framework

# キャッシュクリア
php artisan view:clear || true
php artisan config:clear || true
php artisan route:clear || true

# マイグレーション（失敗しても継続）
php artisan migrate --force || true

# Apache 起動
apache2-foreground
