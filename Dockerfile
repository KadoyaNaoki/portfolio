# PHP + Apache
FROM php:8.2-apache

# 必要な拡張をインストール
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql

# Node.js（公式）をインストール（※ Node18 → Node20 に変更推奨）
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
RUN apt-get install -y nodejs

# Composer をインストール
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Laravel を配置
WORKDIR /var/www/html
COPY . .

# Composer install
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Node パッケージ
RUN npm install --legacy-peer-deps

# Vite ビルド
RUN npm run build

# Apache の DocumentRoot を Laravel の public に設定
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# entrypoint.sh をコピー
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD ["/entrypoint.sh"]
