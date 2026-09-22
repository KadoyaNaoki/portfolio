# PHP + Apache
FROM php:8.2-apache

# 必要な拡張をインストール
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composer をインストール
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Laravel を配置
WORKDIR /var/www/html
COPY . .

# ビルド処理
RUN composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build \
    && php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache

# Apache の DocumentRoot を Laravel の public に設定
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
