# Cloudflare Tunnel - クイックスタート（本環境用）

## 本環境の詳細情報

### アカウント情報
```
ユーザー名: b0023035
ホームディレクトリ: /home/b0023035
プロジェクト: /home/b0023035/ProgrammerAptitudeTest
```

### Cloudflare Tunnel情報
```
トンネル名: minmi-tunnel
トンネルID: 60eb9d8c-3154-4ff8-8192-b78045564bc3
ドメイン: aws-sample-minmi.click
```

### 設定ファイル
```
Tunnel認証: /home/b0023035/.cloudflared/cert.pem
Tunnel設定: /home/b0023035/.cloudflared/config.yml
認証情報: /home/b0023035/.cloudflared/60eb9d8c-3154-4ff8-8192-b78045564bc3.json
```

### Docker コンテナ
```
アプリケーション: programmer-test-app (HTTP:80, HTTPS:443)
データベース: programmer-test-db (MySQL:3306)
キャッシュ: programmer-test-redis (Redis:6379)
```

---

## 現在の状態

✅ **全て準備完了 - Tunnel起動のみ待機状態**

- Cloudflaredインストール完了
- Tunnel作成完了（接続状態: オンライン）
- Config.yml 設定完了
- Docker コンテナ全て正常起動（healthy）
- HTTPS接続テスト成功

---

## 1分でできる！今すぐ起動

### 方法1: 前景で起動（テスト用）

```bash
cloudflared tunnel run minmi-tunnel
```

**期待される出力：**
```
INF Starting tunnel minmi-tunnel
INF Registering tunnel connection from <location>
INF Tunnel registered successfully
INF Connected to CDN
```

### 方法2: バックグラウンド起動（本運用）

```bash
nohup cloudflared tunnel run minmi-tunnel > /tmp/cloudflared.log 2>&1 &
```

### 方法3: Systemd サービス起動（推奨・永続化）

```bash
# 初回のみ：サービスファイル作成
sudo tee /etc/systemd/system/cloudflared.service > /dev/null << 'EOF'
[Unit]
Description=Cloudflare Tunnel
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=b0023035
WorkingDirectory=/home/b0023035
ExecStart=/usr/local/bin/cloudflared tunnel run minmi-tunnel
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# 有効化と起動
sudo systemctl enable cloudflared
sudo systemctl start cloudflared

# 状態確認
sudo systemctl status cloudflared
```

---

## 動作確認

### 1. ローカルから確認

```bash
# ローカルホストアクセス
curl -s https://localhost/ -k | head -20

# ヘルスチェック
curl -s http://localhost/health
# 出力: OK

# Tunnel接続確認
cloudflared tunnel list
# 出力: CONNECTIONS が 1+ なら接続中
```

### 2. リモートから確認（別のPC/ターミナルから）

```bash
# URLアクセス
curl -s https://aws-sample-minmi.click/

# ブラウザアクセス
# https://aws-sample-minmi.click
```

---

## ログ確認

### Tunnel ログ確認

```bash
# Systemd の場合
sudo journalctl -u cloudflared -f

# nohup の場合
tail -f /tmp/cloudflared.log

# Docker ログ
docker compose -f docker-compose.production.yml logs -f app
```

---

## よく使うコマンド

```bash
# Tunnel起動確認
cloudflared tunnel list

# Tunnel停止
pkill cloudflared

# Tunnel設定確認
cat ~/.cloudflared/config.yml

# Docker 状態確認
cd /home/b0023035/ProgrammerAptitudeTest
docker compose -f docker-compose.production.yml ps

# ポート確認
ss -tlnp | grep -E '80|443|3306|6379'

# プロセス確認
ps aux | grep cloudflared
```

---

## トラブルシューティング

### 「Permission denied」エラー

```bash
# 実行権限確認
ls -la ~/.cloudflared/config.yml

# 権限修正
chmod 600 ~/.cloudflared/config.yml
```

### 「Cannot find tunnel」エラー

```bash
# トンネル一覧確認
cloudflared tunnel list

# 出力に minmi-tunnel が表示されているか確認
# 表示されない場合: トンネル作成が必要
```

### 接続できない場合

```bash
# 1. Docker 状態確認
docker compose -f docker-compose.production.yml ps
# 全て healthy か Up 状態か確認

# 2. ポート確認
ss -tlnp | grep -E '443'
# :443 がリッスン中か確認

# 3. Tunnel ログ確認
tail -f /tmp/cloudflared.log
# エラーメッセージ確認

# 4. 接続テスト
curl -s https://localhost/ -k
# ローカルアクセスが成功するか確認
```

---

## 関連ドキュメント

- [詳細セットアップガイド](CLOUDFLARE_TUNNEL_SETUP.md) - 詳細な手順書
- [Cloudflareセットアップガイド](CLOUDFLARE_SETUP_GUIDE.md) - 初期セットアップ（アカウント作成等）
- [環境設定マニュアル](ENVIRONMENT_CONFIG_MANUAL.md) - 環境変数設定
- [移行ガイド](manual/06_MIGRATION_GUIDE.md) - 別PCでの移行方法

---

## 問い合わせ時の情報提供

トラブルが発生した場合は以下の情報を提供してください：

```bash
# 1. Tunnel状態
cloudflared tunnel list

# 2. Docker 状態
docker compose -f docker-compose.production.yml ps

# 3. Tunnel ログ（最新20行）
tail -20 /tmp/cloudflared.log

# 4. Docker ログ（最新50行）
docker compose -f docker-compose.production.yml logs app | tail -50

# 5. ポート状態
ss -tlnp | grep -E '80|443|3306|6379'
```

---

**最終更新**: 2026-03-05
**環境**: b0023035 @ AWS / Cloudflare Tunnel
