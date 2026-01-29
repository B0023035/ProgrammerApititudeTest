#!/bin/bash
# Docker コンテナ化テストスクリプト

set -e

echo "🧪 Docker コンテナ化テスト開始"
echo "================================"
echo ""

# 1. Docker のインストール確認
echo "✓ Docker 環境チェック"
if ! command -v docker &> /dev/null; then
    echo "  ❌ Docker がインストールされていません"
    exit 1
else
    DOCKER_VERSION=$(docker --version)
    echo "  ✅ $DOCKER_VERSION"
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "  ❌ Docker Compose がインストールされていません"
    exit 1
else
    echo "  ✅ Docker Compose インストール済み"
fi
echo ""

# 2. イメージのビルドテスト
echo "✓ Docker イメージビルドテスト"
echo "  イメージをビルド中... （数分かかります）"
if docker build -t programmer-test:test . > /tmp/docker_build.log 2>&1; then
    echo "  ✅ ビルド成功"
    BUILT_IMAGE=$(docker images programmer-test:test --format "{{.Repository}}:{{.Tag}}")
    SIZE=$(docker images programmer-test:test --format "{{.Size}}")
    echo "  イメージ: $BUILT_IMAGE"
    echo "  サイズ: $SIZE"
else
    echo "  ❌ ビルド失敗"
    echo "  エラーログ:"
    tail -20 /tmp/docker_build.log
    exit 1
fi
echo ""

# 3. コンテナ起動テスト
echo "✓ コンテナ起動テスト"
echo "  テスト環境変数を設定中..."

# テスト用 .env を作成
cat > .env.test << EOF
APP_NAME="ProgrammerAptitudeTest - Test"
APP_ENV=production
APP_KEY=base64:YR6+2/V2FahkVKRwVL5rf4Y2rDCF+XDxUS0985/sFAU=
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel_test
DB_USERNAME=sail
DB_PASSWORD=test_password_123

CACHE_DRIVER=redis
SESSION_DRIVER=database
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
LOG_CHANNEL=stack
LOG_LEVEL=warning

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
EOF

echo "  コンテナを起動中... （初回は数秒かかります）"
if docker-compose -f docker-compose.prod.yml -p test up -d > /tmp/docker_up.log 2>&1; then
    echo "  ✅ コンテナ起動成功"
else
    echo "  ❌ コンテナ起動失敗"
    echo "  エラーログ:"
    tail -20 /tmp/docker_up.log
    exit 1
fi
echo ""

# 4. 起動待機
echo "✓ コンテナ起動待機中..."
WAIT_COUNT=0
MAX_WAIT=60
while [ $WAIT_COUNT -lt $MAX_WAIT ]; do
    if curl -f http://localhost/health > /dev/null 2>&1; then
        echo "  ✅ ヘルスチェック成功"
        break
    fi
    WAIT_COUNT=$((WAIT_COUNT + 1))
    echo -n "."
    sleep 1
done

if [ $WAIT_COUNT -ge $MAX_WAIT ]; then
    echo ""
    echo "  ⚠️  ヘルスチェックがタイムアウト"
fi
echo ""

# 5. コンテナのステータス確認
echo "✓ コンテナのステータス確認"
docker-compose -f docker-compose.prod.yml -p test ps
echo ""

# 6. ログ確認
echo "✓ コンテナログ確認"
echo "  アプリケーションログ:"
docker-compose -f docker-compose.prod.yml -p test logs app | tail -20
echo ""

# 7. ポート確認
echo "✓ ポート確認"
if netstat -tulpn 2>/dev/null | grep -q ":80 "; then
    echo "  ✅ ポート 80 が開いています"
else
    echo "  ⚠️  ポート 80 が開いていません"
fi
echo ""

# 8. データベース確認
echo "✓ データベース接続確認"
if docker-compose -f docker-compose.prod.yml -p test exec -T db mysql -u sail -ptest_password_123 -e "SELECT 1;" > /dev/null 2>&1; then
    echo "  ✅ MySQL 接続成功"
else
    echo "  ⚠️  MySQL 接続失敗"
fi
echo ""

# 9. Redis 確認
echo "✓ Redis 接続確認"
if docker-compose -f docker-compose.prod.yml -p test exec -T redis redis-cli ping > /dev/null 2>&1; then
    echo "  ✅ Redis 接続成功"
else
    echo "  ⚠️  Redis 接続失敗"
fi
echo ""

# 10. クリーンアップ
echo "✓ テスト環境をクリーンアップ中..."
docker-compose -f docker-compose.prod.yml -p test down -v > /dev/null 2>&1
rm -f .env.test

echo "================================"
echo "✅ テスト完了"
echo ""
echo "次のステップ:"
echo "1. 本番環境の .env ファイルを設定: cp .env.example.production .env"
echo "2. 環境変数を編集: nano .env"
echo "3. 本番環境で起動: docker-compose -f docker-compose.prod.yml up -d"
echo ""
