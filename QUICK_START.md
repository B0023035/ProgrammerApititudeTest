# ProgrammerAptitudeTest 完全セットアップガイド

## 前提条件
- Docker & Docker Compose インストール済み
- Git インストール済み
- インターネット接続あり

---

## Step 1: プロジェクト取得

```bash
# ホームディレクトリに移動
cd ~

# GitHubからクローン
git clone https://github.com/B0023035/ProgrammerApititudeTest.git

# プロジェクトディレクトリに移動
cd ProgrammerApititudeTest
```

---

## Step 2: 環境準備

```bash
# 環境ファイル作成
cp .env.example .env

# 必要なディレクトリ作成
mkdir -p logs/mysql certbot/conf certbot/www
```

---

## Step 3: Dockerビルド

```bash
# イメージをビルド（初回は5-10分程度）
docker compose -f docker-compose.production.yml build --no-cache
```

---

## Step 4: コンテナ起動

```bash
# コンテナ起動
docker compose -f docker-compose.production.yml up -d
```

---

## Step 5: 起動確認

```bash
# ログ確認（以下のメッセージが出ればOK）
docker logs programmer-test-app --tail 50
```

**成功時の出力例:**
```
✅ マイグレーション完了
🌱 初回セットアップを検出。シーディングを実行します...
- questions: 95件
- choices: 475件
- practice_questions: 8件
- practice_choices: 40件
✅ シーディング完了
✅ 起動準備完了
==========================================
🎉 システムが正常に起動しました
==========================================
```

---

## Step 6: アクセス確認

```bash
# HTTPSアクセス確認
curl -sk https://localhost | head -20
```

**ブラウザでアクセス:**
- アプリ: https://localhost
- 管理画面: https://localhost/admin/login
  - Email: `admin@provisional`
  - Password: `P@ssw0rd`

---

## 全コマンド一括実行（コピペ用）

```bash
# === 完全セットアップ（1回でOK） ===
cd ~
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerApititudeTest
cp .env.example .env
mkdir -p logs/mysql certbot/conf certbot/www
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
sleep 20
docker logs programmer-test-app --tail 30
```

---

## 既存環境を削除して再インストール

```bash
# === 完全削除＆再インストール ===
cd ~/ProgrammerApititudeTest

# コンテナ・ボリューム停止削除
docker compose -f docker-compose.production.yml down -v

# Dockerリソース完全クリア
docker system prune -a --volumes -f

# プロジェクト削除（Docker作成ファイル含む）
cd ~
docker run --rm -v ~:/data alpine:latest rm -rf /data/ProgrammerApititudeTest

# 再クローン＆ビルド
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerApititudeTest
cp .env.example .env
mkdir -p logs/mysql certbot/conf certbot/www
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
sleep 20
docker logs programmer-test-app --tail 30
```

---

## 日常運用コマンド

### 起動
```bash
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml up -d
```

### 停止
```bash
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down
```

### 再起動
```bash
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml restart
```

### ログ確認
```bash
docker logs programmer-test-app --tail 50
docker logs -f programmer-test-app  # リアルタイム
```

### 状態確認
```bash
docker ps
```

---

## Cloudflare設定（外部公開する場合）

### cloudflaredインストール（sudoなし）
```bash
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -o /tmp/cloudflared
mkdir -p ~/.local/bin
mv /tmp/cloudflared ~/.local/bin/cloudflared
chmod +x ~/.local/bin/cloudflared
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
export PATH="$HOME/.local/bin:$PATH"
cloudflared --version
```

### Quick Tunnel（一時URL、アカウント不要）
```bash
# 一時公開URL取得
cloudflared tunnel --url https://localhost:443 --no-tls-verify

# バックグラウンド実行
nohup cloudflared tunnel --url https://localhost:443 --no-tls-verify > /tmp/cloudflared.log 2>&1 &
sleep 5
cat /tmp/cloudflared.log | grep trycloudflare
```

### 本番ドメイン設定
詳細は `CLOUDFLARE_SETUP_GUIDE.md` を参照

---

## トラブルシューティング

### コンテナが起動しない
```bash
docker logs programmer-test-app
```

### ポート競合
```bash
# 80/443ポートを使用しているプロセス確認
sudo lsof -i :80
sudo lsof -i :443
```

### 完全リセット
```bash
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down -v
docker system prune -a --volumes -f
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
```

---

## 確認済み動作

✅ 2026-03-17 クリーンビルドテスト完了
- マイグレーション: 成功（practice_choicesテーブルのpartカラム含む）
- シーディング: 成功（エラーなし）
- HTTPS: 動作確認済み
- 管理画面: アクセス確認済み

---

*作成日: 2026-03-17*
