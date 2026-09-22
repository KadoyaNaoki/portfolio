#!/bin/bash

# storage ディレクトリを確実に作成
mkdir -p storage/logs
mkdir -p storage/framework/sessions
mkdir -p storage/framework/cache
mkdir -p storage/framework/views

# Laravel の書き込み権限（起動後に実行されるので確実に効く）
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache storage/logs storage/framework

# キャッシュクリア
php artisan view:clear
php artisan config:clear
php artisan route:clear

# マイグレーション（失敗しても継続）
php artisan migrate --force || true

# Apache 起動
apache2-foreground
