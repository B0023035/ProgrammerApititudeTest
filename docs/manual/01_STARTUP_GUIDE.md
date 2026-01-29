# 起動方法ガイド

プログラマー適性試験システムの起動・停止方法を説明します。

## 目次
1. [前提条件](#前提条件)
2. [起動方法](#起動方法)
3. [停止方法](#停止方法)
4. [ログの確認](#ログの確認)
5. [トラブルシューティング](#トラブルシューティング)

---

## 前提条件

### 必要なソフトウェア
- **Docker Desktop** または **Docker Engine** (v20.10以上)
- **Docker Compose** (v2.0以上)
- **Cloudflare Tunnel (cloudflared)** (外部公開する場合)
- **Git** (ソースコード管理)
- **Node.js** (v18以上、フロントエンドビルド用)

### ポート要件
| ポート | 用途 |
|--------|------|
| 80 | HTTP（HTTPSにリダイレクト） |
| 443 | HTTPS（メインアクセス） |
| 3306 | MySQL（内部通信用） |
| 6379 | Redis（内部通信用） |

---

## 起動方法

### 1. Dockerサービスの起動

**Linux/WSL2の場合:**
```bash
sudo service docker start
```

**Docker Desktopの場合:**
Docker Desktopアプリケーションを起動してください。

### 2. アプリケーションの起動

プロジェクトディレクトリに移動し、以下のコマンドを実行：

```bash
cd /home/[ユーザー名]/ProgrammerAptitudeTest

# 本番環境用コンテナを起動
docker compose -f docker-compose.production.yml up -d
```

### 3. 起動確認

```bash
# コンテナの状態を確認
docker compose -f docker-compose.production.yml ps

# 期待される出力:
# NAME                      STATUS
# programmer-test-app       Up (healthy)
# programmer-test-db        Up (healthy)
# programmer-test-redis     Up (healthy)
```

### 4. Cloudflare Tunnelの起動（外部公開する場合）

```bash
# Cloudflare Tunnelを起動
cloudflared tunnel run minmi-tunnel
```

### 5. アクセス確認

```bash
# ローカルでの確認
curl -s -o /dev/null -w "%{http_code}" https://localhost/

# Cloudflare経由での確認
curl -s -o /dev/null -w "%{http_code}" https://aws-sample-minmi.click/
```

---

## 停止方法

### アプリケーションの停止

```bash
cd /home/[ユーザー名]/ProgrammerAptitudeTest

# コンテナを停止（データは保持）
docker compose -f docker-compose.production.yml stop

# コンテナを完全に削除（データは保持）
docker compose -f docker-compose.production.yml down

# コンテナとボリュームを完全に削除（データも削除）
docker compose -f docker-compose.production.yml down -v
```

### Cloudflare Tunnelの停止

```bash
# Ctrl+C で停止
# または別ターミナルから
pkill cloudflared
```

---

## ログの確認

### アプリケーションログ

```bash
# 全コンテナのログ
docker compose -f docker-compose.production.yml logs

# アプリケーションのみ
docker compose -f docker-compose.production.yml logs app

# リアルタイムで追跡
docker compose -f docker-compose.production.yml logs -f app

# Laravelログ
docker exec programmer-test-app cat /var/www/html/storage/logs/laravel.log | tail -50
```

### Nginxログ

```bash
# アクセスログ
docker exec programmer-test-app cat /var/log/nginx/access.log | tail -50

# エラーログ
docker exec programmer-test-app cat /var/log/nginx/error.log | tail -50
```

---

## トラブルシューティング

### 問題: コンテナが起動しない

```bash
# ログを確認
docker compose -f docker-compose.production.yml logs app

# コンテナを再作成
docker compose -f docker-compose.production.yml up -d --force-recreate
```

### 問題: 500エラーが発生

```bash
# キャッシュをクリア
docker exec programmer-test-app php artisan config:clear
docker exec programmer-test-app php artisan cache:clear
docker exec programmer-test-app php artisan config:cache
```

### 問題: データベース接続エラー

```bash
# データベースの状態確認
docker exec programmer-test-db mysql -u sail -ppassword -e "SELECT 1;"

# マイグレーション実行
docker exec programmer-test-app php artisan migrate --force
```

### 問題: Redisエラー

```bash
# Redis接続確認
docker exec programmer-test-redis redis-cli ping
# 期待: PONG
```

### 問題: Nginx設定エラー（コンテナ再起動後）

```bash
# fastcgi_passの修正（毎回必要な場合）
docker exec programmer-test-app sed -i 's|fastcgi_pass unix:/var/run/php-fpm.sock;|fastcgi_pass 127.0.0.1:9000;|g' /etc/nginx/http.d/default.conf
docker exec programmer-test-app nginx -s reload
```

---

## 一括起動スクリプト

以下のスクリプトを `start-production.sh` として保存すると便利です：

```bash
#!/bin/bash
set -e

echo "🚀 本番環境を起動しています..."

# Dockerサービスの起動
sudo service docker start 2>/dev/null || true
sleep 3

# プロジェクトディレクトリに移動
cd /home/b0023035/ProgrammerAptitudeTest

# コンテナ起動
docker compose -f docker-compose.production.yml up -d

# 起動待機
echo "⏳ コンテナの起動を待機中..."
sleep 15

# Nginx設定修正
docker exec programmer-test-app sed -i 's|fastcgi_pass unix:/var/run/php-fpm.sock;|fastcgi_pass 127.0.0.1:9000;|g' /etc/nginx/http.d/default.conf 2>/dev/null || true
docker exec programmer-test-app nginx -s reload 2>/dev/null || true

# キャッシュ再生成
docker exec programmer-test-app php artisan config:cache

echo "✅ 起動完了！"
echo "📍 ローカル: https://localhost/"
echo "📍 外部: https://aws-sample-minmi.click/ (Tunnel起動後)"

# Cloudflare Tunnel起動（バックグラウンド）
# cloudflared tunnel run minmi-tunnel &
```

**使用方法:**
```bash
chmod +x start-production.sh
./start-production.sh
```
