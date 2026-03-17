#!/bin/sh
# Docker 起動スクリプト（本番環境用）
# ビルドするだけで全自動セットアップ

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

# ==========================================
# APP_KEY 自動生成（未設定の場合）
# ==========================================
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "🔑 APP_KEY が未設定です。自動生成します..."
    export APP_KEY=$(php artisan key:generate --show)
    echo "✅ APP_KEY を生成しました: $APP_KEY"
fi

# ==========================================
# データベース接続待機
# ==========================================
echo "⏳ データベース接続を待機中..."
MAX_RETRIES=60
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if php -r "
        \$host = getenv('DB_HOST') ?: 'db';
        \$port = getenv('DB_PORT') ?: '3306';
        \$database = getenv('DB_DATABASE') ?: 'laravel';
        \$user = getenv('DB_USERNAME') ?: 'sail';
        \$pass = getenv('DB_PASSWORD') ?: 'password';
        try {
            new PDO(\"mysql:host=\$host;port=\$port;dbname=\$database\", \$user, \$pass);
            echo 'connected';
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null; then
        echo "✅ データベースに接続しました"
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "  データベース接続待機中... ($RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "❌ データベース接続がタイムアウトしました"
    exit 1
fi

# ==========================================
# 自動マイグレーション
# ==========================================
echo "📊 データベースマイグレーションを実行中..."

# マイグレーション実行（--forceで本番環境でも実行）
php artisan migrate --force

echo "✅ マイグレーション完了"

# ==========================================
# 初回セットアップ判定とシーディング
# ==========================================
# usersテーブルにデータがなければ初回セットアップとみなす
USER_COUNT=$(php -r "
    \$host = getenv('DB_HOST') ?: 'db';
    \$port = getenv('DB_PORT') ?: '3306';
    \$database = getenv('DB_DATABASE') ?: 'laravel';
    \$user = getenv('DB_USERNAME') ?: 'sail';
    \$pass = getenv('DB_PASSWORD') ?: 'password';
    try {
        \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$database\", \$user, \$pass);
        \$stmt = \$pdo->query('SELECT COUNT(*) FROM users');
        echo \$stmt ? \$stmt->fetchColumn() : '0';
    } catch (Exception \$e) {
        echo '0';
    }
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
    echo "🌱 初回セットアップを検出。シーディングを実行します..."
    php artisan db:seed --force
    echo "✅ シーディング完了"
else
    echo "ℹ️  既存データが存在するためシーディングをスキップします"
fi

# ==========================================
# Laravel 起動準備
# ==========================================
echo "📝 Laravel 起動準備中..."

# ストレージリンク作成
php artisan storage:link 2>/dev/null || true

# キャッシュ設定（本番環境用）
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

echo "✅ 起動準備完了"
echo ""
echo "=========================================="
echo "🎉 システムが正常に起動しました"
echo "=========================================="
echo ""
echo "Starting Supervisor..."

# Supervisor を起動（すべてのサービスを管理）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
