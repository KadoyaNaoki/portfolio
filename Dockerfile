# PHP + Apache
FROM php:8.2-apache

# 必要な拡張をインストール
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Node.js（公式）をインストール
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs

# Composer をインストール
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Laravel を配置
WORKDIR /var/www/html
COPY . .

# Composer install（高速化）
RUN composer config -g process-timeout 2000
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Node パッケージ
RUN npm install

# Vite ビルド
RUN npm run build

# Laravel キャッシュ
RUN php artisan config:cache
RUN php artisan route:cache

# Apache の DocumentRoot を Laravel の public に設定
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

EXPOSE 80
CMD ["apache2-foreground"]
