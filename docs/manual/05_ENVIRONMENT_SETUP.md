# 環境設定マニュアル

プログラマー適性試験システムの環境設定について説明します。

## 目次

1. [必要なソフトウェア](#必要なソフトウェア)
2. [初期セットアップ](#初期セットアップ)
3. [環境変数設定](#環境変数設定)
4. [Docker設定](#docker設定)
5. [Cloudflare Tunnel設定](#cloudflare-tunnel設定)
6. [SSL証明書設定](#ssl証明書設定)
7. [データベース設定](#データベース設定)

---

## 必要なソフトウェア

### 必須

| ソフトウェア   | バージョン | 用途                 |
| -------------- | ---------- | -------------------- |
| Docker         | 20.10+     | コンテナ実行環境     |
| Docker Compose | 2.0+       | マルチコンテナ管理   |
| Git            | 2.30+      | ソースコード管理     |
| Node.js        | 18+        | フロントエンドビルド |
| npm            | 9+         | パッケージ管理       |

### 外部公開時に必要

| ソフトウェア | バージョン | 用途              |
| ------------ | ---------- | ----------------- |
| cloudflared  | 最新       | Cloudflare Tunnel |

### 開発時に追加で必要

| ソフトウェア | バージョン | 用途            |
| ------------ | ---------- | --------------- |
| PHP          | 8.3+       | ローカル開発    |
| Composer     | 2.0+       | PHP依存関係管理 |

---

## 初期セットアップ

### 1. リポジトリのクローン

```bash
git clone [リポジトリURL] ProgrammerAptitudeTest
cd ProgrammerAptitudeTest
```

### 2. 環境変数ファイルの作成

```bash
cp .env.example .env
```

### 3. Node.js依存関係のインストール

```bash
npm install
```

### 4. フロントエンドのビルド

```bash
npm run build
```

### 5. Dockerイメージのビルド

```bash
docker compose -f docker-compose.production.yml build
```

### 6. コンテナの起動

```bash
docker compose -f docker-compose.production.yml up -d
```

### 7. データベースマイグレーション

```bash
docker exec programmer-test-app php artisan migrate --force
```

### 8. 初期データの投入（必要な場合）

```bash
docker exec programmer-test-app php artisan db:seed
```

---

## 環境変数設定

### .env ファイルの主要設定

```bash
# ====================
# アプリケーション設定
# ====================
APP_NAME="プログラマー適性試験"
APP_ENV=production
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
APP_DEBUG=false
APP_URL=https://your-domain.com

# ====================
# データベース設定
# ====================
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# ====================
# Redis設定
# ====================
REDIS_HOST=redis
REDIS_PASSWORD=
REDIS_PORT=6379

# ====================
# セッション設定
# ====================
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=your-domain.com

# ====================
# キャッシュ設定
# ====================
CACHE_DRIVER=redis

# ====================
# メール設定（パスワードリセット用）
# ====================
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### APP_KEYの生成

```bash
# ローカルでPHPがある場合
php artisan key:generate

# Dockerコンテナ内で実行
docker exec programmer-test-app php artisan key:generate
```

---

## Docker設定

### docker-compose.production.yml の重要設定

```yaml
services:
    app:
        environment:
            # 必ず設定が必要
            - APP_KEY=${APP_KEY} # 暗号化キー
            - APP_ENV=production # 本番環境
            - APP_DEBUG=false # デバッグ無効
            - DB_HOST=db # DBホスト
            - REDIS_HOST=redis # Redisホスト
```

### ボリューム設定

```yaml
volumes:
    mysql-data: # データベース永続化
    redis-data: # セッション永続化
    app-storage: # アップロードファイル
    app-cache: # Laravelキャッシュ
```

### ポート設定

```yaml
services:
    app:
        ports:
            - "80:80" # HTTP
            - "443:443" # HTTPS
    db:
        ports:
            - "3306:3306" # MySQL（必要な場合のみ公開）
```

---

## Cloudflare Tunnel設定

### 1. cloudflaredのインストール

**Linux/WSL2:**

```bash
# Debianベースのシステム
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
```

**Windows:**

```powershell
winget install cloudflare.cloudflared
```

### 2. Cloudflareへのログイン

```bash
cloudflared tunnel login
```

ブラウザが開くので、Cloudflareアカウントでログインし、対象ドメインを選択。

### 3. トンネルの作成

```bash
cloudflared tunnel create [tunnel-name]
# 例: cloudflared tunnel create minmi-tunnel
```

トンネルIDが表示されるのでメモしておく。

### 4. DNS設定

```bash
cloudflared tunnel route dns [tunnel-name] [hostname]
# 例: cloudflared tunnel route dns minmi-tunnel aws-sample-minmi.click
```

### 5. 設定ファイルの作成

`~/.cloudflared/config.yml` を作成:

```yaml
tunnel: [トンネルID]
credentials-file: /home/[ユーザー名]/.cloudflared/[トンネルID].json

ingress:
    - hostname: your-domain.com
      service: https://localhost:443
      originRequest:
          noTLSVerify: true
    - service: http_status:404
```

### 6. トンネルの起動

```bash
cloudflared tunnel run [tunnel-name]
```

---

## SSL証明書設定

### Cloudflare Origin CA証明書

#### 1. 証明書の取得

1. Cloudflareダッシュボードにログイン
2. 対象ドメインを選択
3. SSL/TLS → Origin Server
4. 「Create Certificate」をクリック
5. 秘密鍵タイプ: RSA
6. ホスト名: `*.your-domain.com`, `your-domain.com`
7. 有効期間: 15年（推奨）
8. 「Create」をクリック

#### 2. 証明書の保存

```bash
# ディレクトリ作成
mkdir -p certbot/conf/live/your-domain.com

# 証明書を保存（Cloudflareの画面からコピー）
# fullchain.pem: Origin Certificate
# privkey.pem: Private Key
```

#### 3. Nginx設定

`docker/default.conf` の SSL部分:

```nginx
ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
```

---

## データベース設定

### 初期データのリストア

```bash
# バックアップファイルからリストア
docker exec -i programmer-test-db mysql -u sail -ppassword laravel < backup.sql
```

### バックアップの作成

```bash
# データベースのバックアップ
docker exec programmer-test-db mysqldump -u sail -ppassword laravel > backup_$(date +%Y%m%d).sql
```

### 接続確認

```bash
# MySQL接続テスト
docker exec programmer-test-db mysql -u sail -ppassword -e "SELECT 1;"

# テーブル一覧
docker exec programmer-test-db mysql -u sail -ppassword laravel -e "SHOW TABLES;"
```

---

## トラブルシューティング

### ポート80が使用中

**Windowsの場合:**

```powershell
# 使用しているプロセスを確認
netstat -ano | findstr :80

# プロセスを終了（管理者権限）
taskkill /F /PID [PID番号]
```

**Linux/WSL2の場合:**

```bash
# 使用しているプロセスを確認
sudo lsof -i :80

# Apache/Nginxを停止
sudo service apache2 stop
sudo service nginx stop
```

### Nginx設定エラー（コンテナ再起動後）

```bash
# fastcgi_passの修正
docker exec programmer-test-app sed -i 's|fastcgi_pass unix:/var/run/php-fpm.sock;|fastcgi_pass 127.0.0.1:9000;|g' /etc/nginx/http.d/default.conf
docker exec programmer-test-app nginx -s reload
```

### キャッシュ問題

```bash
# 全キャッシュクリア
docker exec programmer-test-app php artisan optimize:clear
docker exec programmer-test-app php artisan config:cache
docker exec programmer-test-app php artisan route:cache
docker exec programmer-test-app php artisan view:cache
```
