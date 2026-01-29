# ProgrammerAptitudeTest デプロイメントガイド

このドキュメントは、別のPCでこのプロジェクトをセットアップし、Cloudflare Tunnel経由で外部公開するための手順をまとめています。

---

## 目次

1. [必要要件](#1-必要要件)
2. [プロジェクトのセットアップ](#2-プロジェクトのセットアップ)
3. [Dockerコンテナの起動](#3-dockerコンテナの起動)
4. [Cloudflare Tunnelの設定](#4-cloudflare-tunnelの設定)
5. [動作確認](#5-動作確認)
6. [トラブルシューティング](#6-トラブルシューティング)

---

## 1. 必要要件

### ソフトウェア

- **OS**: Ubuntu 22.04+ (WSL2対応)
- **Docker**: 24.0+
- **Docker Compose**: 2.20+
- **Git**: 2.40+

### アカウント

- **Cloudflare**: 無料アカウント（ドメイン管理用）
- **ドメイン**: aws-sample-minmi.click（または自分のドメイン）

---

## 2. プロジェクトのセットアップ

### 2.1 リポジトリのクローン

```bash
cd ~
git clone <リポジトリURL> ProgrammerAptitudeTest
cd ProgrammerAptitudeTest
```

### 2.2 環境変数の設定

```bash
cp .env.example .env
```

`.env`ファイルを編集：

```env
APP_NAME=ProgrammerAptitudeTest
APP_ENV=production
APP_KEY=base64:YR6+2/V2FahkVKRwVL5rf4Y2rDCF+XDxUS0985/sFA
APP_DEBUG=false
APP_URL=https://aws-sample-minmi.click

DB_CONNECTION=mysql
DB_HOST=prod-db
DB_PORT=3306
DB_DATABASE=laravel_prod
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=prod-redis
REDIS_PASSWORD=
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

MAIL_MAILER=log
```

**注意**: `APP_KEY`は`php artisan key:generate`で新しく生成することを推奨

---

## 3. Dockerコンテナの起動

### 3.1 本番環境用コンテナのビルドと起動

```bash
cd ~/ProgrammerAptitudeTest

# コンテナをビルド
docker compose -f docker-compose.prod-test.yml build --no-cache prod-app

# コンテナを起動
docker compose -f docker-compose.prod-test.yml up -d

# 起動確認
docker compose -f docker-compose.prod-test.yml ps
```

### 3.2 期待される出力

```
NAME                        STATUS              PORTS
prog-test-prod-app          Up (healthy)        0.0.0.0:80->80/tcp
prog-test-prod-db           Up (healthy)        0.0.0.0:3307->3306/tcp
prog-test-prod-redis        Up (healthy)        0.0.0.0:6380->6379/tcp
prog-test-prod-phpmyadmin   Up                  0.0.0.0:8080->80/tcp
```

### 3.3 データベースのマイグレーション（初回のみ）

```bash
docker exec prog-test-prod-app php artisan migrate --force
docker exec prog-test-prod-app php artisan db:seed --force
```

### 3.4 ローカル動作確認

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost/
# 200 が返ればOK
```

---

## 4. Cloudflare Tunnelの設定

### 4.1 cloudflaredのインストール

```bash
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb -o /tmp/cloudflared.deb
sudo dpkg -i /tmp/cloudflared.deb
```

### 4.2 Cloudflareにログイン

```bash
cloudflared tunnel login
```

- ブラウザが開くのでCloudflareアカウントでログイン
- ドメイン「aws-sample-minmi.click」を選択
- 証明書が `~/.cloudflared/cert.pem` にダウンロードされる

### 4.3 トンネルの作成

```bash
cloudflared tunnel create minmi-tunnel
```

出力例：

```
Tunnel credentials written to /home/<user>/.cloudflared/<tunnel-id>.json
Created tunnel minmi-tunnel with id <tunnel-id>
```

**重要**: `<tunnel-id>` をメモしておく

### 4.4 DNSルートの設定

```bash
cloudflared tunnel route dns minmi-tunnel aws-sample-minmi.click
```

**注意**: 既存のAレコードがある場合は、先にCloudflareダッシュボードで削除する

### 4.5 設定ファイルの作成

```bash
mkdir -p ~/.cloudflared

cat > ~/.cloudflared/config.yml << 'EOF'
tunnel: minmi-tunnel
credentials-file: /home/<user>/.cloudflared/<tunnel-id>.json

ingress:
  - hostname: aws-sample-minmi.click
    service: http://localhost:80
  - service: http_status:404
EOF
```

**注意**: `<user>` と `<tunnel-id>` は実際の値に置き換える

### 4.6 トンネルの起動

#### 手動起動（テスト用）

```bash
cloudflared tunnel run minmi-tunnel
```

#### バックグラウンド起動

```bash
nohup cloudflared tunnel run minmi-tunnel > /tmp/cloudflared.log 2>&1 &
```

### 4.7 システムサービスとして登録（オプション）

```bash
sudo mkdir -p /etc/cloudflared
sudo cp ~/.cloudflared/config.yml /etc/cloudflared/
sudo cp ~/.cloudflared/*.json /etc/cloudflared/
sudo cp ~/.cloudflared/cert.pem /etc/cloudflared/

sudo cloudflared service install
sudo systemctl enable cloudflared
sudo systemctl start cloudflared
```

---

## 5. 動作確認

### 5.1 外部からのアクセス確認

```bash
curl -s -o /dev/null -w "%{http_code}" https://aws-sample-minmi.click/
# 200 が返ればOK
```

### 5.2 ブラウザで確認

https://aws-sample-minmi.click/ にアクセスし、ログイン画面が表示されることを確認

---

## 6. トラブルシューティング

### 6.1 コンテナが起動しない

```bash
# ログを確認
docker compose -f docker-compose.prod-test.yml logs prod-app

# コンテナを再起動
docker compose -f docker-compose.prod-test.yml restart prod-app
```

### 6.2 トンネルが接続できない

```bash
# トンネルの状態確認
cloudflared tunnel list

# ログを確認
cat /tmp/cloudflared.log

# トンネルを再起動
pkill cloudflared
cloudflared tunnel run minmi-tunnel &
```

### 6.3 DNSが解決しない

- Cloudflareダッシュボードでレコードを確認
- 既存のAレコードが残っていないか確認
- CNAMEレコードが正しく設定されているか確認

### 6.4 502 Bad Gateway

```bash
# Dockerコンテナが動作しているか確認
docker ps

# ローカルでアクセスできるか確認
curl http://localhost/

# コンテナを再起動
docker compose -f docker-compose.prod-test.yml restart
```

---

## 付録

### A. よく使うコマンド

```bash
# コンテナ起動
docker compose -f docker-compose.prod-test.yml up -d

# コンテナ停止
docker compose -f docker-compose.prod-test.yml down

# コンテナ再ビルド
docker compose -f docker-compose.prod-test.yml build --no-cache prod-app

# コンテナログ確認
docker compose -f docker-compose.prod-test.yml logs -f prod-app

# トンネル起動
cloudflared tunnel run minmi-tunnel &

# トンネル停止
pkill cloudflared

# キャッシュクリア
docker exec prog-test-prod-app php artisan config:clear
docker exec prog-test-prod-app php artisan cache:clear
```

### B. ポート一覧

| サービス   | ローカルポート | 用途                   |
| ---------- | -------------- | ---------------------- |
| Webアプリ  | 80             | メインアプリケーション |
| MySQL      | 3307           | データベース           |
| Redis      | 6380           | キャッシュ/セッション  |
| phpMyAdmin | 8080           | DB管理画面             |

### C. PC再起動後の起動手順

```bash
# 1. Dockerコンテナ起動
cd ~/ProgrammerAptitudeTest
docker compose -f docker-compose.prod-test.yml up -d

# 2. コンテナが起動するまで待機
sleep 10

# 3. Cloudflare Tunnel起動
cloudflared tunnel run minmi-tunnel &

# 4. 動作確認
curl -s https://aws-sample-minmi.click/
```

---

## 更新履歴

- 2026-01-28: 初版作成
