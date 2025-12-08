#!/bin/sh
# Docker 起動スクリプト

set -e

echo "🚀 アプリケーション起動中..."

# Laravel キャッシュをクリア
echo "📝 キャッシュのクリア..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# マイグレーション実行（必要な場合）
echo "🗄️  データベースマイグレーション実行..."
php artisan migrate --force || true

# ストレージのパーミッション設定
echo "🔒 パーミッション設定..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# ログディレクトリ作成
mkdir -p /var/log/php-fpm /var/log/supervisor
touch /var/log/php-error.log /var/log/php-fpm-access.log /var/log/laravel-worker.log
chown www-data:www-data /var/log/php-error.log /var/log/php-fpm-access.log /var/log/laravel-worker.log

echo "✅ 起動準備完了"
echo "Starting Supervisor..."

# Supervisor を起動（すべてのサービスを管理）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
