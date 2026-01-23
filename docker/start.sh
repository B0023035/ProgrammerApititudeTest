#!/bin/sh
# Docker 起動スクリプト（本番環境用）

set -e

echo "🚀 アプリケーション起動中..."

# ディレクトリ作成
mkdir -p /var/lib/php/sessions
mkdir -p /var/log/nginx
mkdir -p /var/log/supervisor
mkdir -p /run/nginx
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# パーミッション設定
echo "🔒 パーミッション設定..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/lib/php/sessions
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ログファイル作成
touch /var/log/php-error.log /var/log/php-fpm-access.log /var/log/php-fpm-slow.log
chown www-data:www-data /var/log/php-error.log /var/log/php-fpm-access.log /var/log/php-fpm-slow.log

# Laravel 起動準備
echo "📝 Laravel 起動準備中..."

# ストレージリンク作成
php artisan storage:link 2>/dev/null || true

# キャッシュ設定（本番環境用）
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "✅ 起動準備完了"
echo "Starting Supervisor..."

# Supervisor を起動（すべてのサービスを管理）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
