#!/bin/bash

# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# マイグレーション（失敗しても継続）
php artisan migrate --force || true

# Apache 起動
apache2-foreground
