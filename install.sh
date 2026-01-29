#!/bin/bash
# ==========================================
# プログラマー適性試験システム - インストールスクリプト
# ==========================================

set -e

echo "=========================================="
echo "プログラマー適性試験システム インストーラー"
echo "=========================================="
echo ""

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# 1. 前提条件チェック
echo "1. 前提条件をチェックしています..."

if ! command -v docker &> /dev/null; then
    error "Dockerがインストールされていません。先にDockerをインストールしてください。"
fi
success "Docker: $(docker --version)"

if ! command -v docker compose &> /dev/null; then
    error "Docker Composeがインストールされていません。"
fi
success "Docker Compose: $(docker compose version)"

# 2. 環境変数ファイルの確認
echo ""
echo "2. 環境設定をチェックしています..."

if [ ! -f ".env" ]; then
    error ".envファイルが見つかりません。.env.exampleをコピーして設定してください。"
fi
success ".envファイルが存在します"

# APP_KEYの確認
if ! grep -q "APP_KEY=base64:" .env; then
    warning "APP_KEYが設定されていません。生成します..."
    # 一時的にコンテナを起動してキーを生成
    docker compose -f docker-compose.production.yml run --rm app php artisan key:generate --show > /tmp/app_key.txt
    APP_KEY=$(cat /tmp/app_key.txt | grep "base64:")
    sed -i "s|APP_KEY=.*|APP_KEY=$APP_KEY|" .env
    success "APP_KEYを生成しました"
fi

# 3. Dockerイメージのビルド
echo ""
echo "3. Dockerイメージをビルドしています..."
docker compose -f docker-compose.production.yml build --no-cache
success "Dockerイメージのビルドが完了しました"

# 4. コンテナの起動
echo ""
echo "4. コンテナを起動しています..."
docker compose -f docker-compose.production.yml up -d
success "コンテナを起動しました"

# 5. データベースの準備を待機
echo ""
echo "5. データベースの準備を待機しています..."
sleep 10

# データベースが準備できるまで待機
for i in {1..30}; do
    if docker exec programmer-test-db mysql -u sail -ppassword -e "SELECT 1" &> /dev/null; then
        success "データベースが準備できました"
        break
    fi
    echo "  データベース準備中... ($i/30)"
    sleep 2
done

# 6. Nginx設定の修正
echo ""
echo "6. Nginx設定を適用しています..."
docker exec programmer-test-app sed -i 's|fastcgi_pass unix:/var/run/php-fpm.sock;|fastcgi_pass 127.0.0.1:9000;|g' /etc/nginx/http.d/default.conf 2>/dev/null || true
docker exec programmer-test-app nginx -s reload 2>/dev/null || true
success "Nginx設定を適用しました"

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
