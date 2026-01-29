#!/bin/bash
set -e

echo "🚀 コンテナ起動処理を開始..."

# storage と bootstrap/cache の権限を設定
echo "📁 ストレージ権限を設定中..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 必要なディレクトリを作成
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/logs

chmod -R 775 /var/www/html/storage/framework

# Redisの起動を待つ
echo "⏳ Redisの起動を待機中..."
until php -r "try { \$redis = new Redis(); \$redis->connect('redis', 6379); echo 'Redis OK'; } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    echo "   Redisに接続中..."
    sleep 2
done
echo "✅ Redis接続成功"

# キャッシュをクリア
echo "🧹 キャッシュをクリア中..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

echo "✅ 起動処理完了"

# 元のコマンドを実行
exec "$@"