# 環境セットアップガイド

このドキュメントは、別のPCでこの開発環境を完全に再現するための手順を説明します。

---

## クイックスタート（別のPCでの再現方法）

```bash
# 1. リポジトリをクローン
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerApititudeTest

# 2. 自動セットアップ実行
./scripts/setup-environment.sh
```

VS Codeでプロジェクトを開くと、推奨拡張機能のインストールを促すポップアップが表示されます。「すべてインストール」を選択してください。

---

## 作成された環境再現用ファイル

| ファイル | 説明 |
|---------|------|
| `.vscode/extensions.json` | VS Code推奨拡張機能リスト（自動インストール促進） |
| `.vscode/settings.json` | プロジェクト固有のVS Code設定 |
| `scripts/install-extensions.sh` | 拡張機能一括インストールスクリプト |
| `scripts/setup-environment.sh` | 環境自動セットアップスクリプト |

### インストールされるVS Code拡張機能

| 拡張機能ID | 説明 |
|-----------|------|
| `github.copilot` | GitHub Copilot |
| `github.copilot-chat` | GitHub Copilot Chat |
| `vue.volar` | Vue.js サポート |
| `esbenp.prettier-vscode` | コードフォーマッター |
| `ms-ceintl.vscode-language-pack-ja` | 日本語言語パック |
| `aaron-bond.better-comments` | コメント装飾 |
| `celianriboulet.webvalidator` | Web検証ツール |
| `ecmel.vscode-html-css` | HTML/CSSサポート |
| `formulahendry.auto-rename-tag` | タグ自動リネーム |
| `mosapride.zenkaku` | 全角文字チェック |
| `streetsidesoftware.code-spell-checker` | スペルチェッカー |
| `tabnine.tabnine-vscode` | AI補完 |

---

## 必要な環境

### バージョン情報
| ソフトウェア | バージョン |
|-------------|-----------|
| Node.js | v22.21.0 |
| npm | 11.6.2 |
| PHP | 8.3.6 |
| Composer | 2.8.12 |

## セットアップ手順

### 1. 前提ソフトウェアのインストール

#### Windows (WSL2推奨)
```bash
# WSL2をインストール（PowerShell管理者権限）
wsl --install -d Ubuntu

# WSL2内で以下を実行
```

#### Node.js (nvm推奨)
```bash
# nvmをインストール
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
source ~/.bashrc

# Node.js v22をインストール
nvm install 22.21.0
nvm use 22.21.0
```

#### PHP 8.3
```bash
# Ubuntu/Debian
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.3 php8.3-cli php8.3-common php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-mysql php8.3-redis php8.3-gd php8.3-bcmath
```

#### Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. リポジトリのクローン

```bash
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerApititudeTest
```

### 3. 依存関係のインストール

```bash
# PHP依存関係
composer install

# Node.js依存関係
npm install
```

### 4. 環境設定

```bash
# .envファイルの作成
cp .env.example .env

# アプリケーションキーの生成
php artisan key:generate
```

### 5. データベースのセットアップ

#### Dockerを使用する場合（推奨）
```bash
# Docker Composeで起動
docker-compose up -d

# マイグレーション実行
php artisan migrate
```

#### ローカルMySQLを使用する場合
`.env`ファイルのデータベース設定を変更：
```
DB_HOST=127.0.0.1
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. ビルドと起動

```bash
# アセットのビルド
npm run build

# 開発サーバー起動
composer dev
# または
php artisan serve & npm run dev
```

## VS Code拡張機能

プロジェクトを開くと、推奨拡張機能のインストールを促されます。
すべてインストールすることで、同じ開発体験が得られます。

### 手動でインストールする場合

```bash
code --install-extension aaron-bond.better-comments
code --install-extension celianriboulet.webvalidator
code --install-extension ecmel.vscode-html-css
code --install-extension esbenp.prettier-vscode
code --install-extension formulahendry.auto-rename-tag
code --install-extension github.copilot
code --install-extension github.copilot-chat
code --install-extension mosapride.zenkaku
code --install-extension ms-ceintl.vscode-language-pack-ja
code --install-extension streetsidesoftware.code-spell-checker
code --install-extension tabnine.tabnine-vscode
code --install-extension vue.volar
```

### 一括インストールスクリプト

```bash
# プロジェクトルートで実行
./scripts/install-extensions.sh
```

## Docker環境での起動

Docker Composeを使用すると、すべての依存サービスを簡単に起動できます：

```bash
# 開発環境
docker-compose -f docker-compose.dev.yml up -d

# 本番環境
docker-compose -f docker-compose.prod.yml up -d

# スタンドアロン
docker-compose -f docker-compose.standalone.yml up -d
```

## テスト実行

```bash
# PHPテスト
php artisan test

# Playwrightテスト
npm test

# または
npx playwright test
```

## トラブルシューティング

### WSL2ネットワーク問題
- [WSL2_NETWORK_GUIDE.md](WSL2_NETWORK_GUIDE.md) を参照

### CSRF関連エラー
- [CSRF_FIX_MINIMAL.md](CSRF_FIX_MINIMAL.md) を参照

### Docker関連
- [DOCKER_DEPLOYMENT.md](DOCKER_DEPLOYMENT.md) を参照

## ファイル構成

```
.vscode/
├── extensions.json  # 推奨拡張機能リスト
└── settings.json    # プロジェクト固有設定

scripts/
└── install-extensions.sh  # 拡張機能一括インストール
```
