#!/bin/bash
# ==========================================
# 本番環境デプロイスクリプト
# 150人同時接続対応
# ==========================================

set -e

echo "=================================================="
echo "🚀 本番環境デプロイスクリプト"
echo "   150人同時接続対応版"
echo "=================================================="

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# プロジェクトディレクトリ
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

# ログディレクトリ作成
mkdir -p logs/mysql logs/nginx

# 関数: メッセージ表示
info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ステップ1: 環境チェック
echo ""
echo "=================================================="
echo "📋 ステップ1: 環境チェック"
echo "=================================================="

# Dockerチェック
if ! command -v docker &> /dev/null; then
    error "Docker がインストールされていません"
    exit 1
fi
success "Docker: $(docker --version)"

# Docker Composeチェック
if ! command -v docker compose &> /dev/null; then
    if ! command -v docker-compose &> /dev/null; then
        error "Docker Compose がインストールされていません"
        exit 1
    fi
    COMPOSE_CMD="docker-compose"
else
    COMPOSE_CMD="docker compose"
fi
success "Docker Compose: OK"

# ステップ2: 環境変数設定
echo ""
echo "=================================================="
echo "📋 ステップ2: 環境変数設定"
echo "=================================================="

if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        cp .env.production .env
        info ".env.production を .env にコピーしました"
    else
        error ".env ファイルが見つかりません"
        exit 1
    fi
fi

# APP_KEY がない場合は生成
if ! grep -q "^APP_KEY=base64:" .env; then
    warning "APP_KEY が設定されていません。生成します..."
    APP_KEY=$(openssl rand -base64 32)
    sed -i "s/^APP_KEY=$/APP_KEY=base64:${APP_KEY}/" .env
    success "APP_KEY を生成しました"
fi

success "環境変数設定: OK"

# ステップ3: 既存コンテナの停止
echo ""
echo "=================================================="
echo "📋 ステップ3: 既存コンテナの停止"
echo "=================================================="

info "既存コンテナを停止しています..."
$COMPOSE_CMD -f docker-compose.production.yml down --remove-orphans 2>/dev/null || true
success "既存コンテナを停止しました"

# ステップ4: Dockerイメージのビルド
echo ""
echo "=================================================="
echo "📋 ステップ4: Dockerイメージのビルド"
echo "=================================================="

info "本番環境用イメージをビルドしています..."
info "（初回は時間がかかります）"

$COMPOSE_CMD -f docker-compose.production.yml build --no-cache app

success "イメージのビルドが完了しました"

# ステップ5: コンテナの起動
echo ""
echo "=================================================="
echo "📋 ステップ5: コンテナの起動"
echo "=================================================="

info "コンテナを起動しています..."
$COMPOSE_CMD -f docker-compose.production.yml up -d

success "コンテナが起動しました"

# ステップ6: 起動待機
echo ""
echo "=================================================="
echo "📋 ステップ6: サービス起動待機"
echo "=================================================="

info "データベースの起動を待機しています..."
sleep 10

# データベース起動確認
MAX_RETRIES=30
RETRY_COUNT=0
while ! $COMPOSE_CMD -f docker-compose.production.yml exec -T db mysqladmin ping -h localhost -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2)" --silent 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        error "データベースの起動に失敗しました"
        exit 1
    fi
    info "データベース起動待機中... ($RETRY_COUNT/$MAX_RETRIES)"
    sleep 2
done

success "データベースが起動しました"

# ステップ7: マイグレーション実行
echo ""
echo "=================================================="
echo "📋 ステップ7: データベースマイグレーション"
echo "=================================================="

info "マイグレーションを実行しています..."
$COMPOSE_CMD -f docker-compose.production.yml exec -T app php artisan migrate --force

success "マイグレーションが完了しました"

# ステップ8: キャッシュ最適化
echo ""
echo "=================================================="
echo "📋 ステップ8: キャッシュ最適化"
echo "=================================================="

info "Laravelキャッシュを最適化しています..."
$COMPOSE_CMD -f docker-compose.production.yml exec -T app php artisan config:cache
$COMPOSE_CMD -f docker-compose.production.yml exec -T app php artisan route:cache
$COMPOSE_CMD -f docker-compose.production.yml exec -T app php artisan view:cache

success "キャッシュ最適化が完了しました"

# ステップ9: 状態確認
echo ""
echo "=================================================="
echo "📋 ステップ9: サービス状態確認"
echo "=================================================="

$COMPOSE_CMD -f docker-compose.production.yml ps

# ステップ10: ヘルスチェック
echo ""
echo "=================================================="
echo "📋 ステップ10: ヘルスチェック"
echo "=================================================="

sleep 5

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    success "ヘルスチェック: OK (HTTP $HTTP_CODE)"
else
    warning "ヘルスチェック: 応答コード $HTTP_CODE"
fi

# 完了メッセージ
echo ""
echo "=================================================="
echo "✅ デプロイ完了"
echo "=================================================="
echo ""
echo "🌐 アプリケーションURL:"
echo "   http://$(hostname -I | awk '{print $1}')"
echo ""
echo "📊 サーバー情報:"
echo "   - Nginx: 4096 connections"
echo "   - PHP-FPM: max 100 workers"
echo "   - MySQL: max 500 connections"
echo "   - Redis: max 1000 clients"
echo ""
echo "📝 ログ確認:"
echo "   $COMPOSE_CMD -f docker-compose.production.yml logs -f app"
echo ""
echo "🔄 サービス再起動:"
echo "   $COMPOSE_CMD -f docker-compose.production.yml restart"
echo ""
echo "🛑 サービス停止:"
echo "   $COMPOSE_CMD -f docker-compose.production.yml down"
echo ""
