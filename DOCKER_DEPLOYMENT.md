# Docker コンテナ化による公開・デプロイメント

このガイドは、プログラマー適性検査システムを Docker コンテナ化して公開する手順です。

## 🎯 公開準備チェックリスト

- [ ] Docker & Docker Compose がインストール済み
- [ ] リポジトリをクローン済み
- [ ] `.env` ファイルが作成済み
- [ ] 本番環境のドメイン用意済み
- [ ] データベース用意済み（本番環境）
- [ ] SSL証明書取得済み（Let's Encrypt）

## 📋 ステップ1: 環境構築

### 1.1 ローカルテスト環境で動作確認

```bash
# プロジェクトディレクトリに移動
cd ProgrammerAptitudeTest

# 開発環境用docker-composeで起動（既存）
./vendor/bin/sail up -d

# ブラウザで http://localhost にアクセス
# 試験を実施してテスト
```

### 1.2 本番用環境変数を設定

```bash
# テンプレートからコピー
cp .env.example.production .env.prod

# 本番環境用に編集
nano .env.prod

# 編集が必要な項目:
# - APP_URL=https://your-domain.com
# - DB_PASSWORD (強力なパスワード)
# - REDIS_PASSWORD (設定する場合)
# - MAIL_HOST, MAIL_PORT (本番メールサーバー)
# - MAIL_FROM_ADDRESS (本番環境のメールアドレス)
```

### 1.3 公開前チェック実行

```bash
# 設定をチェック
bash deployment-check.sh
```

## 🐳 ステップ2: Docker イメージのビルド

```bash
# 本番環境用イメージをビルド
docker build -t programmer-test:latest .

# イメージサイズを確認
docker images | grep programmer-test

# オプション: Docker Hub にプッシュ
docker tag programmer-test:latest your-dockerhub-username/programmer-test:latest
docker push your-dockerhub-username/programmer-test:latest
```

## 🚀 ステップ3: 本番環境へのデプロイ

### 方法A: VPS/自社サーバーでのデプロイ

```bash
# 1. サーバーにSSHでアクセス
ssh user@your-server.com

# 2. Docker & Docker Compose をインストール
sudo apt update
sudo apt install -y docker.io docker-compose git

# 3. Docker をユーザーに許可
sudo usermod -aG docker $USER
newgrp docker

# 4. プロジェクトをクローン
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerApititudeTest

# 5. 本番環境設定ファイルをコピー
cp .env.example.production .env

# 6. 環境変数を編集
nano .env
# 本番用の値を設定

# 7. コンテナを起動
docker-compose -f docker-compose.prod.yml up -d

# 8. マイグレーションを実行
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 9. ログを確認
docker-compose -f docker-compose.prod.yml logs -f app
```

### 方法B: AWS ECS でのデプロイ

```bash
# 1. ECR（Amazon Elastic Container Registry）にイメージをプッシュ
aws ecr get-login-password --region ap-northeast-1 | docker login --username AWS --password-stdin 123456789.dkr.ecr.ap-northeast-1.amazonaws.com

docker tag programmer-test:latest 123456789.dkr.ecr.ap-northeast-1.amazonaws.com/programmer-test:latest
docker push 123456789.dkr.ecr.ap-northeast-1.amazonaws.com/programmer-test:latest

# 2. ECS タスク定義を作成・更新
# AWS Console で ECS → タスク定義 → 新規作成

# 3. ECS サービスを作成
# AWS Console で ECS → サービス → 作成

# 4. Load Balancer を設定
# AWS Console で EC2 → Load Balancer
```

### 方法C: Docker Hub からのデプロイ

```bash
# 1. サーバーで直接プル・実行
docker pull your-dockerhub-username/programmer-test:latest

# 2. docker-compose.yml で参照
# image: your-dockerhub-username/programmer-test:latest を使用

docker-compose -f docker-compose.prod.yml up -d
```

## 🔐 ステップ4: SSL/HTTPS 設定

### Let's Encrypt で無料SSL証明書を取得

```bash
# Certbot をインストール
sudo apt install -y certbot python3-certbot-nginx

# 証明書を取得
sudo certbot certonly --standalone -d your-domain.com -d www.your-domain.com

# 自動更新を設定
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# 証明書の場所
# /etc/letsencrypt/live/your-domain.com/fullchain.pem
# /etc/letsencrypt/live/your-domain.com/privkey.pem
```

### Nginx での SSL 設定

docker-compose.prod.yml で以下を追加:

```yaml
volumes:
  - /etc/letsencrypt:/etc/letsencrypt:ro
```

docker/default.conf に HTTPS リダイレクトを追加:

```nginx
server {
    listen 443 ssl http2;
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    # ... 設定
}

server {
    listen 80;
    return 301 https://$host$request_uri;
}
```

## 📊 ステップ5: 運用・監視

### ログ確認

```bash
# アプリケーションログ
docker-compose -f docker-compose.prod.yml logs -f app

# Nginx ログ
docker-compose -f docker-compose.prod.yml logs -f app | grep nginx

# MySQL ログ
docker-compose -f docker-compose.prod.yml logs -f db

# Redis ログ
docker-compose -f docker-compose.prod.yml logs -f redis
```

### バックアップ

```bash
# データベースバックアップ
docker-compose -f docker-compose.prod.yml exec db \
  mysqldump -u sail -ppassword laravel > backup_$(date +%Y%m%d).sql

# ストレージバックアップ
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/
```

### アップデート

```bash
# コンテナを停止
docker-compose -f docker-compose.prod.yml down

# リポジトリを更新
git pull origin main

# イメージをリビルド
docker build -t programmer-test:latest .

# コンテナを再起動
docker-compose -f docker-compose.prod.yml up -d

# マイグレーションを実行（必要な場合）
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

### ヘルスチェック

```bash
# ヘルスチェック エンドポイント
curl http://localhost/health

# コンテナのステータス確認
docker-compose -f docker-compose.prod.yml ps
```

## 🚨 トラブルシューティング

### コンテナが起動しない

```bash
# ログを確認
docker-compose -f docker-compose.prod.yml logs app

# 一般的な原因:
# 1. ポートが既に使用されている
#    netstat -tulpn | grep :80
# 2. 環境変数が不正
#    nano .env
# 3. ファイル権限エラー
#    chmod -R 755 storage bootstrap/cache
```

### データベース接続エラー

```bash
# MySQL コンテナが起動しているか確認
docker-compose -f docker-compose.prod.yml ps | grep db

# MySQL ログを確認
docker-compose -f docker-compose.prod.yml logs db

# 接続をテスト
docker-compose -f docker-compose.prod.yml exec db \
  mysql -u sail -ppassword -e "SELECT 1;"
```

### メール送信エラー

```bash
# Mailpit UI で確認
# ブラウザで http://your-server:8025 にアクセス

# メール設定を確認
docker-compose -f docker-compose.prod.yml exec app \
  php artisan tinker
  >>> Mail::raw('Test', fn($msg) => $msg->to('test@example.com'));
```

### 高負荷時の対応

```bash
# PHP-FPM ワーカー数を増やす
# docker/www.conf の pm.max_children を増加

# Nginx ワーカー数を増やす
# docker/nginx.conf の worker_processes を増加

# Redis メモリを監視
docker-compose -f docker-compose.prod.yml exec redis \
  redis-cli INFO memory
```

## 📈 パフォーマンス最適化

```bash
# キャッシュを有効化
docker-compose -f docker-compose.prod.yml exec app \
  php artisan config:cache

# ルートをキャッシュ
docker-compose -f docker-compose.prod.yml exec app \
  php artisan route:cache

# ビューをキャッシュ
docker-compose -f docker-compose.prod.yml exec app \
  php artisan view:cache

# Opcache 統計を確認
docker-compose -f docker-compose.prod.yml exec app \
  php -r "echo json_encode(opcache_get_status(), JSON_PRETTY_PRINT);"
```

## 🛡️ セキュリティチェックリスト

- [ ] APP_DEBUG = false
- [ ] APP_ENV = production
- [ ] 強力なDB パスワード設定
- [ ] HTTPS を強制
- [ ] ファイアウォール設定
- [ ] 定期バックアップ設定
- [ ] ログ監視・ローテーション設定
- [ ] セキュリティヘッダー設定完了

## 📞 サポート

問題が発生した場合:
1. ログファイルを確認: `docker-compose logs -f`
2. GitHub Issues で報告
3. ドキュメントを確認: DEPLOYMENT.md
