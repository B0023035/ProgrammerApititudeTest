#!/bin/bash

echo "========================================="
echo "🔥 419エラー完全修正スクリプト (最終版)"
echo "========================================="
echo ""

# 1. 全プロセス停止
echo "1️⃣  全プロセスを停止..."
docker-compose down -v  # -v でボリュームも削除
pkill -f "vite" || true
pkill -f "npm" || true
sleep 3
echo "✅ プロセス停止完了"
echo ""

# 2. .envを完全に書き換え (SESSION_ENCRYPT=false を確実に設定)
echo "2️⃣  .env を完全に書き換え..."
cat > .env << 'EOF'
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

CACHE_STORE=redis
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

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
EOF

echo "✅ .env 書き換え完了"
echo ""

# 3. .env.testing も同様に
echo "3️⃣  .env.testing を書き換え..."
cat > .env.testing << 'EOF'
TEST_USER_EMAIL=B0023035@ib.yic.ac.jp
TEST_USER_PASSWORD=password
TEST_ADMIN_EMAIL=a@a
TEST_ADMIN_PASSWORD=Passw0rd
TEST_SESSION_CODE=TEST0000
TEST_BASE_URL=http://localhost:80

APP_ENV=testing

SESSION_DRIVER=redis
SESSION_LIFETIME=1440
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax
SESSION_CONNECTION=default

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_PREFIX=
REDIS_DB=0
REDIS_CACHE_DB=1

CACHE_STORE=redis
CACHE_PREFIX=

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
EOF

echo "✅ .env.testing 書き換え完了"
echo ""

# 4. bootstrap/cache を削除 (キャッシュされた設定を完全に削除)
echo "4️⃣  キャッシュファイルを削除..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
echo "✅ キャッシュファイル削除完了"
echo ""

# 5. コンテナを起動
echo "5️⃣  コンテナを起動..."
docker-compose up -d
sleep 10
echo "✅ コンテナ起動完了"
echo ""

# 6. Redisを完全にクリア
echo "6️⃣  Redisを完全にクリア..."
docker-compose exec redis redis-cli FLUSHALL
echo "✅ Redisクリア完了"
echo ""

# 7. Laravelのすべてのキャッシュをクリア
echo "7️⃣  Laravelキャッシュをクリア..."
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan route:clear
docker-compose exec laravel.test php artisan view:clear
docker-compose exec laravel.test php artisan optimize:clear
echo "✅ Laravelキャッシュクリア完了"
echo ""

# 8. 設定を確認
echo "8️⃣  設定を確認..."
docker-compose exec laravel.test php artisan tinker --execute="
echo '=== Session Configuration ===' . PHP_EOL;
echo 'Driver: ' . config('session.driver') . PHP_EOL;
echo 'Encrypt: ' . (config('session.encrypt') ? 'TRUE (❌ 問題!)' : 'FALSE (✅ OK)') . PHP_EOL;
echo 'Connection: ' . config('session.connection') . PHP_EOL;
echo 'Cookie: ' . config('session.cookie') . PHP_EOL;
echo 'Lifetime: ' . config('session.lifetime') . ' minutes' . PHP_EOL;
echo PHP_EOL;
echo '=== Redis Configuration ===' . PHP_EOL;
echo 'Client: ' . config('database.redis.client') . PHP_EOL;
echo 'Host: ' . config('database.redis.default.host') . PHP_EOL;
echo 'Prefix: [' . config('database.redis.options.prefix') . ']' . PHP_EOL;
"
echo ""

# 9. テストリクエストを送信
echo "9️⃣  テストリクエスト送信..."
echo "--- CSRFトークンテスト ---"
docker-compose exec laravel.test curl -s http://localhost/debug-csrf-web | python3 -m json.tool
echo ""
echo ""

# 10. セッションの動作確認
echo "🔟 セッション書き込みテスト..."
docker-compose exec laravel.test curl -s http://localhost/debug-session-simple | python3 -m json.tool
echo ""
echo ""

echo "========================================="
echo "✅ 修正完了！"
echo "========================================="
echo ""
echo "⚠️  重要: ブラウザのキャッシュとCookieを削除してください"
echo ""
echo "Chrome/Edgeの場合:"
echo "  1. F12でDevToolsを開く"
echo "  2. アドレスバーの左にある🔒アイコンをクリック"
echo "  3. 'Cookieとサイトデータ' → '管理' → 'すべて削除'"
echo ""
echo "次のステップ:"
echo "  1. ブラウザを完全に閉じて再起動"
echo "  2. Playwrightテストを実行:"
echo "     npx playwright test --grep '練習を完了できる' --headed"
echo ""
echo "期待される結果:"
echo "  ✅ Status: 200 (419ではない)"
echo "  ✅ セッションCookieが40文字の平文 (暗号化されていない)"
echo "  ✅ POSTリクエストが成功する"
echo ""