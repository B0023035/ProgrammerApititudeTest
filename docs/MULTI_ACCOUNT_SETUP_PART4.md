# 別のCloudflareアカウント・ドメイン用セットアップ - 第4部

## 最終検証とバックグラウンド起動

このドキュメントでは、すべての設定が正しく機能していることを確認し、本運用環境での起動方法を説明します。

---

## 📋 前提条件

以下がすべて完了していることを確認してください：

- [ ] 第1部完了：環境変数ファイルの変更
- [ ] 第2部完了：Nginx設定ファイルの変更
- [ ] 第3部完了：Cloudflare設定ファイルの作成
- [ ] Docker コンテナが正常に起動している
- [ ] Tunnel テストが成功している

---

## 🔍 ステップ1：全体の最終確認

### 1.1 環境変数ファイルの確認

```bash
cd /home/【ユーザー名】/ProgrammerAptitudeTest

# 変更内容を確認
echo "=== .env ファイル ===" && \
grep -E "APP_URL|SESSION_DOMAIN" .env && \
echo "" && \
echo "=== .env.production ファイル ===" && \
grep -E "APP_URL|SESSION_DOMAIN|SANCTUM" .env.production
```

**期待される出力**（すべて新しいドメインであること）:
```
=== .env ファイル ===
APP_URL=https://myapp.example.com
SESSION_DOMAIN=myapp.example.com

=== .env.production ファイル ===
APP_URL=https://myapp.example.com
SESSION_DOMAIN=myapp.example.com
SANCTUM_STATEFUL_DOMAINS=myapp.example.com,www.myapp.example.com
```

---

### 1.2 Nginx設定ファイルの確認

```bash
# 旧ドメインが残存していないか確認
echo "=== Nginx 設定確認 ===" && \
grep -r "aws-sample-minmi" docker/default*.conf && echo "⚠️ 旧ドメインが残存しています" || echo "✅ 旧ドメインは削除されています"
```

---

### 1.3 Cloudflare設定ファイルの確認

```bash
# Tunnel 設定を確認
echo "=== Tunnel 設定確認 ===" && \
cat ~/.cloudflared/config.yml && \
echo "" && \
echo "=== Tunnel 一覧確認 ===" && \
cloudflared tunnel list
```

**期待される出力**:
```yaml
tunnel: my-tunnel
credentials-file: /home/newuser/.cloudflared/a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d.json
...
```

---

## 🧪 ステップ2：ローカル接続テスト

### 2.1 Docker コンテナの確認

```bash
# コンテナが全て起動しているか確認
docker compose -f docker-compose.production.yml ps

# 期待される出力：
# NAME                 STATUS
# programmer-test-app  Up (healthy)
# programmer-test-db   Up (healthy)
# programmer-test-redis Up (healthy)
```

---

### 2.2 ローカルホストへのアクセステスト

```bash
# HTTP でのアクセステスト
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost/

# HTTPS でのアクセステスト
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" https://localhost/ -k

# 期待される出力：
# HTTP Status: 200
```

---

### 2.3 ヘルスチェック

```bash
# ヘルスチェックエンドポイントをテスト
curl -s http://localhost/health

# 期待される出力：
# OK
```

---

## 🌐 ステップ3：リモート接続テスト（別PC/ターミナル）

### 3.1 Tunnel を前景で起動

```bash
# ターミナル1: Tunnel起動
cloudflared tunnel run my-tunnel

# または明示的に config.yml を指定
TUNNEL_CONFIG_FILE=~/.cloudflared/config.yml cloudflared tunnel run my-tunnel

# 期待される出力：
# INF Starting tunnel my-tunnel
# INF Tunnel registered successfully
# INF Connected to CDN
```

---

### 3.2 別ターミナルからテスト

**ターミナル2 で実行**:

```bash
# リモートからのアクセステスト
curl -s https://【新しいドメイン】/ | head -10

# ヘッダー確認（Cloudflare経由の確認）
curl -s -I https://【新しいドメイン】/

# 具体例：
# curl -s https://myapp.example.com/ | head -10
# curl -s -I https://myapp.example.com/
```

**期待される出力**:
```
HTTP/2 200
cf-ray: xxxxx-YYY
(アプリケーションのコンテンツ)
```

---

### 3.3 ブラウザでのテスト

ブラウザで以下のURLにアクセス:

```
https://【新しいドメイン】
```

**確認項目**:
- [ ] ページが正常に表示される
- [ ] ブラウザのアドレスバーに🔒マークが表示される
- [ ] コンソール（F12）にエラーが出ていない
- [ ] DBからのデータが正常に表示されている

---

## 🚀 ステップ4：本運用環境での起動

テストが完了したら、本運用用に Tunnel をバックグラウンド起動します。

### 4.1 方法A：nohup での起動（簡易）

```bash
# Tunnel をバックグラウンド起動
nohup cloudflared tunnel run my-tunnel > /tmp/cloudflared.log 2>&1 &

# プロセスID 確認
ps aux | grep cloudflared

# ログ確認
tail -f /tmp/cloudflared.log

# 停止方法
pkill cloudflared
```

---

### 4.2 方法B：Systemd サービス化（推奨）

#### 4.2.1 サービスファイル作成

```bash
# sudo で実行（パスワード入力が求められる場合がある）
sudo tee /etc/systemd/system/cloudflared.service > /dev/null << 'EOF'
[Unit]
Description=Cloudflare Tunnel
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=【新しいユーザー名】
WorkingDirectory=/home/【新しいユーザー名】
ExecStart=/usr/local/bin/cloudflared tunnel run my-tunnel
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
```

**具体例**:
```bash
sudo tee /etc/systemd/system/cloudflared.service > /dev/null << 'EOF'
[Unit]
Description=Cloudflare Tunnel
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=newuser
WorkingDirectory=/home/newuser
ExecStart=/usr/local/bin/cloudflared tunnel run my-tunnel
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
```

---

#### 4.2.2 サービス有効化と起動

```bash
# サービスを有効化（OS起動時に自動起動）
sudo systemctl enable cloudflared

# サービス起動
sudo systemctl start cloudflared

# サービス状態確認
sudo systemctl status cloudflared

# ログ確認
sudo journalctl -u cloudflared -f

# 停止方法
sudo systemctl stop cloudflared
```

---

### 4.3 方法C：Screen/Tmux での起動

```bash
# screen の場合
screen -S cloudflare -d -m cloudflared tunnel run my-tunnel

# 接続確認
screen -ls

# アタッチ（接続）
screen -r cloudflare

# デタッチ（切断）
Ctrl+A → D

# または tmux の場合
tmux new-session -d -s cloudflare "cloudflared tunnel run my-tunnel"
```

---

## ✅ ステップ5：最終確認チェックリスト

### 設定ファイル確認

- [ ] `.env` ファイルに新しいドメインが設定されている
- [ ] `.env.production` ファイルに新しいドメインが設定されている
- [ ] `docker/default.conf` に新しいドメインが設定されている
- [ ] `docker/default-https.conf` に新しいドメインが設定されている
- [ ] `~/.cloudflared/config.yml` に新しいトンネル名が設定されている
- [ ] `~/.cloudflared/config.yml` に正しいトンネルIDが設定されている

### 接続確認

- [ ] ローカルホスト HTTP アクセステスト成功（HTTP 200）
- [ ] ローカルホスト HTTPS アクセステスト成功（HTTP 200）
- [ ] ヘルスチェック成功（OK）
- [ ] Tunnel 前景起動成功
- [ ] リモートからの HTTPS アクセステスト成功
- [ ] ブラウザからのアクセステスト成功

### 本運用準備

- [ ] Tunnel をバックグラウンド起動する方法を決定（nohup/Systemd/Screen）
- [ ] Tunnel が起動したことを確認
- [ ] リモートアクセスが機能していることを確認
- [ ] ログ確認方法を確認

---

## 📊 本運用環境サマリー

```
【新しい環境情報】
ユーザー名: 【新しいユーザー名】
ホームディレクトリ: /home/【新しいユーザー名】
プロジェクトパス: /home/【新しいユーザー名】/ProgrammerAptitudeTest

【Cloudflare情報】
アカウント: 【新しいCloudflareアカウント】
ドメイン: 【新しいドメイン】
トンネル名: 【新しいトンネル名】
トンネルID: 【新しいトンネルID】

【Docker設定】
HTTP ポート: 80
HTTPS ポート: 443
MySQL ポート: 3306
Redis ポート: 6379

【アクセスURL】
https://【新しいドメイン】
```

---

## 🔄 定期メンテナンス

### ログ確認

```bash
# Tunnel ログ確認
sudo journalctl -u cloudflared -f

# アプリケーション ログ確認
docker compose -f docker-compose.production.yml logs -f app

# Docker すべてのログ確認
docker compose -f docker-compose.production.yml logs -f
```

---

### サービス管理

```bash
# Tunnel サービス再起動
sudo systemctl restart cloudflared

# Docker コンテナ再起動
docker compose -f docker-compose.production.yml restart

# システム全体再起動後のサービス確認
sudo systemctl status cloudflared
docker compose -f docker-compose.production.yml ps
```

---

## ✅ 最終完了チェックリスト

すべてのステップが完了していることを確認してください：

- [ ] 第1部：環境変数ファイル変更 ✅
- [ ] 第2部：Nginx設定ファイル変更 ✅
- [ ] 第3部：Cloudflare設定ファイル作成 ✅
- [ ] 第4部：最終検証 ✅
- [ ] ローカル接続テスト成功 ✅
- [ ] リモート接続テスト成功 ✅
- [ ] 本運用環境起動方法決定 ✅
- [ ] Tunnel バックグラウンド起動成功 ✅

---

## 🎉 セットアップ完了

おめでとうございます！別のCloudflareアカウント・ドメインでのセットアップが完了しました。

### 運用開始

```bash
# 確認コマンド
cd /home/【新しいユーザー名】/ProgrammerAptitudeTest

# Docker 状態確認
docker compose -f docker-compose.production.yml ps

# Tunnel 状態確認
cloudflared tunnel list

# アプリケーション ログ確認
docker compose -f docker-compose.production.yml logs app
```

---

## 📞 トラブルシューティング（まとめ）

### よくある問題と対策

| 問題 | 原因 | 対策 |
|-----|-----|------|
| アクセスできない | Tunnel が起動していない | `sudo systemctl start cloudflared` |
| 証明書エラー | SSL設定が不正 | config.yml の `noTLSVerify: true` を確認 |
| ドメイン不一致 | config.yml のドメイン記述誤り | `cat ~/.cloudflared/config.yml` で確認 |
| Docker エラー | Nginx 設定ファイルに誤り | `docker exec programmer-test-app nginx -t` |

---

**このドキュメントではファイルを実際には編集していません。セットアップ完了後も参照できます。**
