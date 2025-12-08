#!/bin/bash

# 🖥️ スタンドアロン セットアップスクリプト
# 用途: Docker を使わずにホストマシン上で直接実行するための環境構築
# 実行: bash setup-standalone.sh

set -e

echo "========================================="
echo "🖥️  スタンドアロン環境セットアップ"
echo "========================================="
echo ""

# 前提条件チェック
echo "📋 前提条件チェック..."
echo ""

# PHP チェック
if ! command -v php &> /dev/null; then
    echo "❌ PHP 8.4 以上がインストールされていません"
    echo "   インストール方法: https://www.php.net/downloads"
    exit 1
fi

PHP_VERSION=$(php -v | head -n 1)
echo "✅ $PHP_VERSION"

# MySQL チェック
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL クライアントがインストールされていません"
    echo "   インストール方法: https://dev.mysql.com/downloads/mysql/"
else
    echo "✅ MySQL クライアント インストール済み"
fi

# Redis チェック
if ! command -v redis-cli &> /dev/null; then
    echo "⚠️  Redis がインストールされていません"
    echo "   インストール方法: https://redis.io/download"
else
    echo "✅ Redis インストール済み"
fi

# Node.js チェック
if ! command -v node &> /dev/null; then
    echo "❌ Node.js 18 以上がインストールされていません"
    echo "   インストール方法: https://nodejs.org/"
    exit 1
fi

NODE_VERSION=$(node -v)
echo "✅ $NODE_VERSION"

# Composer チェック
if ! command -v composer &> /dev/null; then
    echo "❌ Composer がインストールされていません"
    echo "   インストール方法: https://getcomposer.org/download/"
    exit 1
fi

echo "✅ Composer インストール済み"
echo ""

# 環境設定
echo "🔧 環境設定中..."
echo ""

if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ .env ファイルを作成"
else
    echo "ℹ️  .env ファイルは既存です"
fi

# .env を編集
echo ""
echo "📝 .env を編集してください（重要な項目）:"
echo ""
echo "  DB_HOST=127.0.0.1        # ホストマシン上の MySQL"
echo "  DB_PORT=3306"
echo "  DB_DATABASE=laravel"
echo "  DB_USERNAME=root (または作成したユーザー)"
echo "  DB_PASSWORD=password"
echo ""
echo "  REDIS_HOST=127.0.0.1     # ホストマシン上の Redis"
echo "  REDIS_PORT=6379"
echo ""
echo "  APP_URL=http://0.0.0.0:8000"
echo ""

read -p "続行しますか？ (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ キャンセルしました"
    exit 1
fi

# PHP 依存インストール
echo ""
echo "📦 PHP 依存をインストール中（初回は 2-5 分かかります）..."
composer install --no-progress

echo "✅ PHP 依存をインストール完了"
echo ""

# Node.js 依存インストール
echo "📦 Node.js 依存をインストール中..."
npm install

echo "✅ Node.js 依存をインストール完了"
echo ""

# キャッシュ作成
echo "🔧 キャッシュを作成中..."
php artisan config:cache
php artisan route:cache
echo "✅ キャッシュを作成完了"
echo ""

# データベース初期化
echo "🗄️  データベースを初期化中..."
echo ""

read -p "データベースをリセットしますか？ (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate:refresh --seed --force
    echo "✅ データベースをリセット完了"
else
    echo "データベースをリセットスキップ"
fi

echo ""

# フロントエンドビルド
echo "🎨 フロントエンドをビルド中..."
npm run build

echo "✅ フロントエンドをビルド完了"
echo ""

# ストレージパーミッション設定
echo "🔒 ストレージパーミッションを設定中..."
chmod -R 775 storage bootstrap/cache
echo "✅ ストレージパーミッションを設定完了"
echo ""

# 起動方法表示
echo "========================================="
echo "✅ セットアップ完了！"
echo "========================================="
echo ""
echo "🚀 以下のコマンドで起動してください："
echo ""
echo "📍 ターミナル1: Laravel 開発サーバー"
echo "   php artisan serve"
echo ""
echo "📍 ターミナル2: Vite 開発サーバー"
echo "   npm run dev"
echo ""
echo "📍 アクセス:"
echo "   http://localhost:8000"
echo ""
echo "📊 オプション コマンド:"
echo "   php artisan tinker          # Laravel REPL"
echo "   php artisan queue:listen    # キューワーカー"
echo "   php artisan horizon         # キュー監視UI"
echo ""

echo "========================================="
echo "📝 注意事項"
echo "========================================="
echo ""
echo "⚠️  このセットアップはローカル開発用です"
echo "   本番環境では Docker コンテナを使用してください"
echo ""
echo "✅ MySQL と Redis が起動していることを確認してください"
echo ""
echo "💾 バックアップ手順:"
echo "   mysqldump -u root -p laravel > backup.sql"
echo ""

echo ""
echo "質問や問題があれば、ENVIRONMENT_SWITCHING.md を参照してください"
