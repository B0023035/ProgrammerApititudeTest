#!/bin/bash

echo "========================================="
echo "🔥 究極の419エラー修正スクリプト"
echo "========================================="
echo ""

# 1. すべてのプロセスを停止
echo "1️⃣  すべてのプロセスを停止..."
docker-compose down
pkill -f "vite" || true
pkill -f "npm" || true
echo "✅ プロセス停止完了"
echo ""

# 2. .env を完全に書き換え
echo "2️⃣  .env を完全に書き換え..."
cat > .env << 'ENVEOF'
# Docker ユーザー設定
WWWUSER=1000
WWWGROUP=1000

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:YR6+2/V2FahkVKRwVL5rf4Y2rDCF+XDxUS0985/sFAU=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# セッション設定（重要！）
SESSION_DRIVER=redis
SESSION_LIFETIME=1440
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SESSION_CONNECTION=default

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

# キャッシュ設定
CACHE_STORE=redis
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

# Redis設定
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=
REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,192.168.0.0/16,172.16.0.0/12,10.0.0.0/8

VITE_APP_NAME="${APP_NAME}"

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_NO_ANALYTICS=false

VITE_HMR_HOST=null
VITE_HMR_PROTOCOL=null
VITE_HMR_PORT=null
ENVEOF

echo "✅ .env 書き換え完了"
echo ""

# 3. .env.testing を完全に書き換え
echo "3️⃣  .env.testing を完全に書き換え..."
cat > .env.testing << 'TESTENVEOF'
# テスト用アカウント情報
TEST_USER_EMAIL=B0023035@ib.yic.ac.jp
TEST_USER_PASSWORD=password
TEST_ADMIN_EMAIL=a@a
TEST_ADMIN_PASSWORD=Passw0rd

# テスト用セッションコード
TEST_SESSION_CODE=TEST0000

# アプリケーションURL
TEST_BASE_URL=http://localhost:80

# テスト環境設定
APP_ENV=testing

# セッション設定（重要！）
SESSION_DRIVER=redis
SESSION_LIFETIME=1440
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SESSION_CONNECTION=default

# Redis設定
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=
REDIS_DB=0
REDIS_CACHE_DB=1

# キャッシュ設定
CACHE_STORE=redis
CACHE_PREFIX=

# データベース設定
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
TESTENVEOF

echo "✅ .env.testing 書き換え完了"
echo ""

# 4. コンテナを起動
echo "4️⃣  コンテナを起動..."
docker-compose up -d
sleep 5
echo "✅ コンテナ起動完了"
echo ""

# 5. Redisを完全にクリア
echo "5️⃣  Redisを完全にクリア..."
docker-compose exec redis redis-cli FLUSHALL
echo "✅ Redisクリア完了"
echo ""

# 6. Laravelのキャッシュをすべてクリア
echo "6️⃣  Laravelのキャッシュをすべてクリア..."
docker-compose exec laravel.test php artisan optimize:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan route:clear
docker-compose exec laravel.test php artisan view:clear
echo "✅ キャッシュクリア完了"
echo ""

# 7. 設定を確認
echo "7️⃣  設定を確認..."
docker-compose exec laravel.test php artisan tinker --execute="
echo 'Session Driver: ' . config('session.driver') . PHP_EOL;
echo 'Session Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
echo 'Session Connection: ' . config('session.connection') . PHP_EOL;
echo 'Redis Prefix: [' . config('database.redis.options.prefix') . ']' . PHP_EOL;
"
echo ""

# 8. Webテスト
echo "8️⃣  Webからセッションテスト..."
docker-compose exec laravel.test curl -s http://localhost/debug-csrf-web | python3 -m json.tool
echo ""

echo "========================================="
echo "✅ 究極の修正完了！"
echo "========================================="
echo ""
echo "次のステップ:"
echo "1. ブラウザを完全に閉じて再起動"
echo "2. Playwrightテストを実行:"
echo "   npx playwright test --grep '練習を完了できる'"
echo ""
echo "期待される結果:"
echo "  - セッションCookieが40文字の英数字"
echo "  - Status: 200"
echo ""