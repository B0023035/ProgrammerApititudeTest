# ProgrammerAptitudeTest コマンドログ

## 環境情報
- OS: Linux (WSL)
- Docker Compose: production版
- Cloudflare Tunnel: ユーザーディレクトリにインストール（sudoなし）

---

## 1. プロジェクト完全削除＆クローン

```bash
# プロジェクトディレクトリに移動
cd /home/miaru/ProgrammerApititudeTest

# Dockerコンテナ停止・削除
docker compose -f docker-compose.production.yml down -v

# 全Dockerリソース削除
docker system prune -a --volumes -f
docker volume prune -f
docker network prune -f

# プロジェクトフォルダ削除（Docker作成ファイルはroot権限が必要）
cd /home/miaru
docker run --rm -v /home/miaru:/data alpine:latest rm -rf /data/ProgrammerApititudeTest

# GitHubからクローン
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerApititudeTest
```

---

## 2. 環境構築 & Dockerビルド

```bash
# 環境ファイルとディレクトリ作成
cp .env.example .env
mkdir -p logs/mysql certbot/conf certbot/www

# Dockerイメージをビルド（キャッシュなし）
docker compose -f docker-compose.production.yml build --no-cache

# コンテナ起動
docker compose -f docker-compose.production.yml up -d

# ログ確認（マイグレーション・シーディングの成功確認）
docker logs programmer-test-app
```

---

## 3. システム起動・停止コマンド

### 起動
```bash
cd /home/miaru/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml up -d
```

### 停止
```bash
cd /home/miaru/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down
```

### 停止（ボリュームも削除＝データベース初期化）
```bash
cd /home/miaru/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down -v
```

### 再起動
```bash
cd /home/miaru/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml restart
```

### ログ確認
```bash
# 全ログ
docker logs programmer-test-app

# リアルタイムログ
docker logs -f programmer-test-app

# 最新50行
docker logs programmer-test-app --tail 50
```

### コンテナ状態確認
```bash
docker ps
docker compose -f docker-compose.production.yml ps
```

---

## 4. Cloudflare Tunnel セットアップ（新規アカウント用）

### 4.1 cloudflaredインストール（sudoなし）
```bash
# ダウンロード
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -o /tmp/cloudflared

# ユーザーディレクトリに配置
mkdir -p ~/.local/bin
mv /tmp/cloudflared ~/.local/bin/cloudflared
chmod +x ~/.local/bin/cloudflared

# PATHに追加（永続化）
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
export PATH="$HOME/.local/bin:$PATH"

# バージョン確認
cloudflared --version
```

### 4.2 既存認証削除（別アカウントに切り替える場合）
```bash
pkill cloudflared 2>/dev/null
rm -rf ~/.cloudflared
```

### 4.3 Cloudflareアカウントにログイン
```bash
cloudflared tunnel login
# → ブラウザが開くので、Cloudflareアカウントでログインし、ドメインを選択
```

### 4.4 トンネル作成
```bash
# トンネル作成（名前は任意）
cloudflared tunnel create my-tunnel

# 作成されたトンネルID確認
cloudflared tunnel list
```

### 4.5 設定ファイル作成
```bash
# トンネルIDを確認して以下のコマンドを実行（IDは置き換える）
cat > ~/.cloudflared/config.yml << 'EOF'
tunnel: ここにトンネルIDを入れる
credentials-file: /home/miaru/.cloudflared/ここにトンネルID.json

ingress:
  - hostname: あなたのドメイン.com
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
  - service: http_status:404
EOF
```

### 4.6 DNSルーティング設定
```bash
# トンネルとドメインを紐付け
cloudflared tunnel route dns my-tunnel あなたのドメイン.com
```

### 4.7 トンネル起動
```bash
cloudflared tunnel run my-tunnel
```

---

## 5. Cloudflare Tunnel 運用コマンド

### トンネル起動（バックグラウンド）
```bash
nohup cloudflared tunnel run my-tunnel > /tmp/cloudflared.log 2>&1 &
```

### トンネル停止
```bash
pkill cloudflared
```

### トンネル状態確認
```bash
cloudflared tunnel list
cloudflared tunnel info my-tunnel
```

### ログ確認
```bash
cat /tmp/cloudflared.log
tail -f /tmp/cloudflared.log
```

---

## 6. Quick Tunnel（一時URL、アカウント不要）

```bash
# 一時的な公開URL取得（設定不要）
cloudflared tunnel --url https://localhost:443 --no-tls-verify

# バックグラウンドで起動
nohup cloudflared tunnel --url https://localhost:443 --no-tls-verify > /tmp/cloudflared.log 2>&1 &

# URL確認
sleep 5 && cat /tmp/cloudflared.log | grep trycloudflare
```

---

## 7. アクセスURL

### ローカルアクセス
- アプリケーション: https://localhost
- 管理画面: https://localhost/admin/login

### 管理者ログイン（初期設定）
- Email: admin@provisional
- Password: P@ssw0rd
- ※初回ログイン後、必ず変更してください

---

## 8. トラブルシューティング

### コンテナが起動しない
```bash
# ログ確認
docker logs programmer-test-app

# コンテナ内に入る
docker exec -it programmer-test-app sh

# Nginx設定確認
docker exec programmer-test-app cat /etc/nginx/http.d/default.conf
```

### データベースリセット
```bash
docker compose -f docker-compose.production.yml down -v
docker compose -f docker-compose.production.yml up -d
```

### Dockerキャッシュクリア＆完全再ビルド
```bash
docker compose -f docker-compose.production.yml down -v
docker system prune -a --volumes -f
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
```

---

## 9. 全体の起動手順（まとめ）

```bash
# 1. システム起動
cd /home/miaru/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml up -d

# 2. 起動確認（エラーがないか確認）
docker logs programmer-test-app --tail 30

# 3. Cloudflare起動（本番ドメイン使用時）
cloudflared tunnel run my-tunnel

# または Quick Tunnel（一時URL）
cloudflared tunnel --url https://localhost:443 --no-tls-verify
```

## 10. 全体の停止手順（まとめ）

```bash
# 1. Cloudflare停止
pkill cloudflared

# 2. システム停止
cd /home/miaru/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down
```

---

*作成日: 2026-03-17*
