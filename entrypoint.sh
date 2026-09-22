#!/bin/bash

# キャッシュクリア
php artisan view:clear || true
php artisan config:clear || true
php artisan route:clear || true

# マイグレーション（失敗しても継続）
php artisan migrate --force || true

# Apache 起動
apache2-foreground
