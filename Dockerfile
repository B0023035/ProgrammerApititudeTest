# マルチステージビルド - 本番環境用 Dockerfile

# ========================================
# Stage 1: 依存関係のインストール
# ========================================
FROM php:8.4-fpm-alpine AS dependencies

RUN apk add --no-cache \
    build-base \
    autoconf \
    pkgconfig \
    libpng-dev \
    libjpeg-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    mysql-client \
    git \
    curl \
    composer

# PHP拡張機能をインストール
RUN docker-php-ext-configure gd \
      --with-freetype=/usr/include/ \
      --with-jpeg=/usr/include/ && \
    docker-php-ext-install \
      gd \
      zip \
      pdo \
      pdo_mysql \
      bcmath \
      opcache

# Redis拡張をインストール
RUN apk add --no-cache redis && \
    pecl install redis && \
    docker-php-ext-enable redis

# Composer をインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ========================================
# Stage 2: アプリケーション準備
# ========================================
FROM php:8.4-fpm-alpine AS app-builder

# Stage 1 から依存関係をコピー
COPY --from=dependencies /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=dependencies /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=dependencies /usr/bin/composer /usr/bin/composer

# 必要なファイルシステムツールをインストール
RUN apk add --no-cache \
    git \
    curl \
    libpng \
    libjpeg-turbo \
    libfreetype6 \
    libzip \
    mysql-client \
    nginx \
    supervisor

# アプリケーションファイルをコピー
WORKDIR /var/www/html

COPY . /var/www/html/

# Composer でPHP依存関係をインストール（本番用）
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Node.js をインストール
RUN apk add --no-cache nodejs npm

# NPM 依存関係をインストールしてアセットをビルド
RUN npm ci && npm run build

# Laravel キャッシュを作成
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# パーミッション設定
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 storage bootstrap/cache

# ========================================
# Stage 3: 本番環境イメージ
# ========================================
FROM php:8.4-fpm-alpine AS production

# 必要なランタイム依存関係をインストール
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libpng \
    libjpeg-turbo \
    libfreetype6 \
    libzip \
    redis \
    curl

# Stage 2 から PHP拡張をコピー
COPY --from=app-builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=app-builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Stage 2 からアプリケーションをコピー
COPY --from=app-builder /var/www/html /var/www/html

# Nginx 設定をコピー
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/default.conf /etc/nginx/conf.d/default.conf

# Supervisor 設定をコピー
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# PHP 設定（本番環境最適化）
COPY ./docker/php.ini /usr/local/etc/php/php.ini
COPY ./docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# ワーキングディレクトリ
WORKDIR /var/www/html

# ヘルスチェック
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# スタートアップスクリプト
COPY ./docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# ポート公開
EXPOSE 80 443

# スタート
CMD ["/usr/local/bin/start.sh"]
