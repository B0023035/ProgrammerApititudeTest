#!/bin/bash
# ==========================================
# プログラマー適性試験システム - 完全自動インストールスクリプト
# ==========================================
# 使い方: ./install.sh
# 
# このスクリプトは以下を自動で実行します:
# - Docker環境のチェック
# - .env ファイルの自動生成
# - Dockerイメージのビルド（npm, composer含む）
# - データベースの自動マイグレーション
# - 初期データの自動投入（管理者アカウント、問題データ）
#
# ※ Cloudflareの設定以外、すべて自動でセットアップされます
# ==========================================

set -e

echo ""
echo "=========================================="
echo "🚀 プログラマー適性試験システム"
echo "   完全自動インストーラー v2.0"
echo "=========================================="
echo ""

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 関数: 成功メッセージ
success() {
    echo -e "${GREEN}✓ $1${NC}"
}

# 関数: エラーメッセージ
error() {
    echo -e "${RED}✗ $1${NC}"
    exit 1
}

# 関数: 警告メッセージ
warning() {
    echo -e "${YELLOW}! $1${NC}"
}

# 関数: 情報メッセージ
info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# ==========================================
# 1. 前提条件チェック
# ==========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 Step 1/4: 前提条件をチェック中..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if ! command -v docker &> /dev/null; then
    error "Dockerがインストールされていません。先にDockerをインストールしてください。"
fi
success "Docker: $(docker --version | cut -d' ' -f3)"

if ! command -v docker compose &> /dev/null; then
    # 古いバージョンのdocker-composeをチェック
    if command -v docker-compose &> /dev/null; then
        alias docker compose='docker-compose'
        success "Docker Compose (legacy): $(docker-compose --version | cut -d' ' -f4)"
    else
        error "Docker Composeがインストールされていません。"
    fi
else
    success "Docker Compose: $(docker compose version | cut -d' ' -f4)"
fi

# Docker デーモンが起動しているか確認
if ! docker info &> /dev/null; then
    error "Dockerデーモンが起動していません。Dockerを起動してください。"
fi
success "Dockerデーモン: 起動中"

echo ""

# ==========================================
# 2. 環境変数ファイルの自動生成
# ==========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "⚙️  Step 2/4: 環境設定を準備中..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ ! -f ".env" ]; then
    info ".envファイルが見つかりません。自動生成します..."
    
    if [ -f ".env.example" ]; then
        cp .env.example .env
        success ".env.example から .env を生成しました"
    else
        error ".env.example が見つかりません。リポジトリが正しくクローンされているか確認してください。"
    fi
else
    success ".envファイルが存在します"
fi

# APP_KEY の事前生成（Docker外で実行可能な場合）
# ※ APP_KEYが空の場合、コンテナ起動時に自動生成されます
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    info "APP_KEYはコンテナ初回起動時に自動生成されます"
fi

echo ""

# ==========================================
# 3. Dockerイメージのビルド
# ==========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔨 Step 3/4: Dockerイメージをビルド中..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
info "これには数分かかる場合があります（npm/composerインストール含む）"
echo ""

docker compose -f docker-compose.production.yml build
success "Dockerイメージのビルドが完了しました"

echo ""

# ==========================================
# 4. コンテナの起動（自動セットアップ含む）
# ==========================================
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 Step 4/4: システムを起動中..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
info "以下が自動実行されます:"
echo "   - データベース接続待機"
echo "   - マイグレーション実行"
echo "   - 初期データ投入（管理者・問題データ）"
echo "   - Laravel キャッシュ最適化"
echo ""

docker compose -f docker-compose.production.yml up -d
success "コンテナを起動しました"

# セットアップ完了を待機
echo ""
info "システムの初期化を待機中..."

# ヘルスチェック
MAX_WAIT=120
WAITED=0
while [ $WAITED -lt $MAX_WAIT ]; do
    if curl -s http://localhost/health > /dev/null 2>&1; then
        break
    fi
    echo -ne "  初期化中... ($WAITED秒経過)\r"
    sleep 5
    WAITED=$((WAITED + 5))
done

echo ""

# 最終確認
sleep 5

if curl -s http://localhost/health > /dev/null 2>&1; then
    echo ""
    echo "=========================================="
    echo -e "${GREEN}🎉 インストールが完了しました！${NC}"
    echo "=========================================="
    echo ""
    echo "📝 システム情報:"
    echo "   URL: http://localhost"
    echo ""
    echo "🔐 デフォルト管理者アカウント:"
    echo "   Email: admin@provisional"
    echo "   Password: P@ssw0rd"
    echo ""
    echo "⚠️  セキュリティのため、以下を本番環境で変更してください:"
    echo "   1. 管理者のメールアドレスとパスワード"
    echo "   2. .env の DB_PASSWORD, DB_ROOT_PASSWORD"
    echo ""
    echo "📄 ログ確認:"
    echo "   docker compose -f docker-compose.production.yml logs -f app"
    echo ""
    echo "🛑 停止方法:"
    echo "   docker compose -f docker-compose.production.yml down"
    echo ""
    echo "🔄 再起動方法:"
    echo "   docker compose -f docker-compose.production.yml restart"
    echo ""
else
    warning "システムの起動に時間がかかっています"
    echo ""
    echo "以下のコマンドでログを確認してください:"
    echo "   docker compose -f docker-compose.production.yml logs -f app"
    echo ""
fi

# 7. マイグレーションとシード
echo ""
echo "7. データベースをセットアップしています..."
docker exec programmer-test-app php artisan migrate --force
success "マイグレーションが完了しました"

docker exec programmer-test-app php artisan db:seed --force
success "初期データを投入しました"

# 8. キャッシュの生成
echo ""
echo "8. キャッシュを生成しています..."
docker exec programmer-test-app php artisan config:cache
docker exec programmer-test-app php artisan route:cache
docker exec programmer-test-app php artisan view:cache
success "キャッシュを生成しました"

# 9. 完了
echo ""
echo "=========================================="
echo -e "${GREEN}インストールが完了しました！${NC}"
echo "=========================================="
echo ""
echo "アクセスURL:"
echo "  ローカル: http://localhost/"
echo "  管理画面: http://localhost/admin/login"
echo ""
echo "初期管理者アカウント:"
echo "  メール: admin@provisional"
echo "  パスワード: P@ssw0rd"
echo ""
echo -e "${YELLOW}※ 初回ログイン後、パスワードとメールアドレスを変更してください。${NC}"
echo ""
