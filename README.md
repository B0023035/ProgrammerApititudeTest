# プログラマー適性試験システム

新入社員・採用候補者向けのプログラマー適性試験を実施・管理するためのWebシステムです。

## 🚀 クイックスタート（ワンコマンドインストール）

**前提条件**: Docker および Docker Compose がインストールされていること

```bash
# リポジトリをクローン
git clone <repository-url>
cd ProgrammerAptitudeTest

# インストール実行（これだけで完了！）
./install.sh
```

以下が自動で実行されます：
- 環境設定ファイル（.env）の生成
- Dockerイメージのビルド（npm/composerパッケージ含む）
- MySQL/Redisの起動
- データベースマイグレーション
- 初期データ投入（管理者アカウント、問題データ）

## 📋 手動インストール

```bash
# 1. 環境設定ファイルをコピー（オプション：カスタマイズする場合）
cp .env.example .env

# 2. ビルド＆起動
docker compose -f docker-compose.production.yml up --build -d
```

## 🔐 初期ログイン情報

| 項目 | 値 |
|------|-----|
| URL | http://localhost |
| 管理者メール | admin@provisional |
| パスワード | P@ssw0rd |

⚠️ **本番環境では必ず管理者のメールアドレスとパスワードを変更してください**

## 📁 主要コマンド

```bash
# ログ確認
docker compose -f docker-compose.production.yml logs -f app

# 停止
docker compose -f docker-compose.production.yml down

# 再起動
docker compose -f docker-compose.production.yml restart

# 再ビルド
docker compose -f docker-compose.production.yml up --build -d

# データを含めて完全削除
docker compose -f docker-compose.production.yml down -v
```

## 🌐 Cloudflare Tunnel設定（オプション）

外部公開する場合は、Cloudflare Tunnelの設定が必要です。
詳細は [CLOUDFLARE_TUNNEL_SETUP.md](docs/CLOUDFLARE_TUNNEL_SETUP.md) を参照してください。

## 🛠️ 環境設定（.env）

主要な設定項目：

| 変数 | 説明 | デフォルト |
|------|------|-----------|
| APP_URL | アプリケーションURL | http://localhost |
| APP_PORT | HTTPポート | 80 |
| DB_PASSWORD | データベースパスワード | password |
| DB_ROOT_PASSWORD | DBルートパスワード | rootpassword |

## 📚 ドキュメント

- [システムアーキテクチャ](docs/SYSTEM_ARCHITECTURE.md)
- [操作マニュアル](docs/03_OPERATION_MANUAL.md)
- [インストールマニュアル](docs/INSTALLATION_MANUAL.md)
- [Cloudflare設定ガイド](docs/CLOUDFLARE_SETUP_GUIDE.md)

## 🔧 開発環境

開発環境での実行は Laravel Sail を使用します：

```bash
# 依存関係インストール
composer install
npm install

# 開発サーバー起動
./vendor/bin/sail up -d
npm run dev
```

## 📝 技術スタック

- **Backend**: Laravel 11, PHP 8.2
- **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS
- **Database**: MySQL 8.0
- **Cache**: Redis 7.2
- **Server**: Nginx, PHP-FPM
- **Container**: Docker, Docker Compose

## 📄 ライセンス

このプロジェクトはプライベートライセンスです。
