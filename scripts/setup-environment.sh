#!/bin/bash
# 環境セットアップスクリプト
# 新しいPCでこのプロジェクトをセットアップするための自動化スクリプト

set -e

echo "=========================================="
echo "  ProgrammerAptitudeTest 環境セットアップ"
echo "=========================================="

# 色の定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 関数: 成功メッセージ
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# 関数: 警告メッセージ
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 関数: エラーメッセージ
error() {
    echo -e "${RED}❌ $1${NC}"
}

# 関数: バージョンチェック
check_version() {
    echo ""
    echo "📋 環境チェック..."
    
    # Node.js
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node -v)
        success "Node.js: $NODE_VERSION"
    else
        error "Node.js がインストールされていません"
        echo "   推奨: v22.21.0"
        exit 1
    fi
    
    # npm
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm -v)
        success "npm: $NPM_VERSION"
    else
        error "npm がインストールされていません"
        exit 1
    fi
    
    # PHP
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -v | head -1)
        success "PHP: $PHP_VERSION"
    else
        error "PHP がインストールされていません"
        echo "   推奨: PHP 8.3.6"
        exit 1
    fi
    
    # Composer
    if command -v composer &> /dev/null; then
        COMPOSER_VERSION=$(composer -V | head -1)
        success "Composer: $COMPOSER_VERSION"
    else
        error "Composer がインストールされていません"
        exit 1
    fi
}

# 関数: 依存関係のインストール
install_dependencies() {
    echo ""
    echo "📦 依存関係をインストール中..."
    
    # Composer dependencies
    echo "  Composer パッケージをインストール..."
    composer install --no-interaction --prefer-dist
    success "Composer パッケージのインストール完了"
    
    # npm dependencies
    echo "  npm パッケージをインストール..."
    npm install
    success "npm パッケージのインストール完了"
}

# 関数: 環境ファイルのセットアップ
setup_env() {
    echo ""
    echo "⚙️  環境設定をセットアップ中..."
    
    if [ ! -f .env ]; then
        cp .env.example .env
        success ".env ファイルを作成しました"
        
        # アプリケーションキーの生成
        php artisan key:generate
        success "アプリケーションキーを生成しました"
    else
        warning ".env ファイルは既に存在します（スキップ）"
    fi
}

# 関数: VS Code拡張機能のインストール
install_vscode_extensions() {
    echo ""
    echo "🔧 VS Code拡張機能をインストール中..."
    
    if command -v code &> /dev/null; then
        bash scripts/install-extensions.sh
    else
        warning "VS Code (code コマンド) が見つかりません"
        echo "   手動でVS Codeを開き、推奨拡張機能をインストールしてください"
    fi
}

# 関数: ビルド
build_assets() {
    echo ""
    echo "🔨 アセットをビルド中..."
    npm run build
    success "ビルド完了"
}

# メイン処理
main() {
    check_version
    install_dependencies
    setup_env
    build_assets
    install_vscode_extensions
    
    echo ""
    echo "=========================================="
    echo -e "${GREEN}🎉 セットアップが完了しました！${NC}"
    echo "=========================================="
    echo ""
    echo "次のステップ:"
    echo "  1. .env ファイルでデータベース設定を確認"
    echo "  2. docker-compose up -d でDockerを起動"
    echo "  3. php artisan migrate でマイグレーション実行"
    echo "  4. composer dev で開発サーバーを起動"
    echo ""
}

# スクリプト実行
main
