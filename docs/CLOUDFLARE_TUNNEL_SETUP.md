# Cloudflare Tunnel セットアップガイド（別PC環境向け）

このガイドは、**別のPCで aws-sample-minmi.click にアクセスできるようにするため**のCloudflare Tunnelセットアップ手順です。

本環境の実際の設定値：
- **ユーザー名**: b0023035
- **ホームディレクトリ**: /home/b0023035
- **プロジェクト**: /home/b0023035/ProgrammerAptitudeTest
- **トンネル名**: minmi-tunnel
- **トンネルID**: 60eb9d8c-3154-4ff8-8192-b78045564bc3
- **ドメイン**: aws-sample-minmi.click
- **DB名**: programmer_test
- **DBユーザー**: programmer_user

---

## 前提条件

- Linux/WSL2環境
- Cloudflareアカウント（既にaws-sample-minmi.clickドメイン取得済み）
- Docker/Docker Compose インストール済み
- cloudflaredコマンド未インストール

---

## ステップ1: cloudflaredのインストール

### 1.1 Linuxへのインストール

```bash
# 最新版をダウンロード
curl -L --output cloudflared.deb \
  https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb

# インストール
sudo dpkg -i cloudflared.deb

# バージョン確認
cloudflared --version
```

**期待される出力：**
```
cloudflared version XXXX (released ...）
```

### 1.2 インストール確認

```bash
which cloudflared
```

**期待される出力：**
```
/usr/local/bin/cloudflared
```

---

## ステップ2: Cloudflareアカウント認証

### 2.1 ログイン実行

```bash
cloudflared tunnel login
```

**実行後の流れ：**
1. ターミナルに認証URLが表示される
2. ブラウザが自動で開く（開かない場合は、URLを手動で開く）
3. Cloudflareアカウントでログイン
4. `aws-sample-minmi.click` ドメインを選択
5. 「Authorize」をクリック
6. `~/.cloudflared/cert.pem` に証明書が自動保存される

**確認方法：**
```bash
ls -la ~/.cloudflared/cert.pem
```

---

## ステップ3: Tunnel作成

### 3.1 トンネル作成

```bash
cloudflared tunnel create minmi-tunnel
```

**期待される出力：**
```
Tunnel credentials written to /home/[ユーザー名]/.cloudflared/[トンネルID].json
Created tunnel minmi-tunnel with id [トンネルID]
```

**重要：トンネルID（UUID形式）をメモ**
```
例：a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d
```

### 3.2 確認コマンド

```bash
# トンネル一覧表示
cloudflared tunnel list

# 出力例：
# ID                                 NAME         CREATED              CONNECTIONS
# 60eb9d8c-3154-4ff8-8192-b78045564bc3  minmi-tunnel  2024-01-28T13:47:00Z  0/4
```

---

## ステップ4: DNS設定

### 4.1 DNS ルート追加

```bash
cloudflared tunnel route dns minmi-tunnel aws-sample-minmi.click
```

**期待される出力：**
```
Successfully created route for aws-sample-minmi.click
```

### 4.2 Cloudflareダッシュボード確認

1. https://dash.cloudflare.com にアクセス
2. `aws-sample-minmi.click` ドメインを選択
3. 左メニュー「DNS」をクリック
4. 以下のレコードが自動作成されていることを確認：

```
Type    Name                          Content
CNAME   aws-sample-minmi.click        [トンネルID].cfargotunnel.com
```

---

## ステップ5: config.yml 設定ファイル作成

### 5.1 ファイル作成

```bash
cat > ~/.cloudflared/config.yml << 'EOF'
tunnel: minmi-tunnel
credentials-file: /home/b0023035/.cloudflared/60eb9d8c-3154-4ff8-8192-b78045564bc3.json

ingress:
  - hostname: aws-sample-minmi.click
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: aws-sample-minmi.click
  - service: http_status:404
EOF
```

**設定の説明：**
- `tunnel: minmi-tunnel` - トンネル名
- `credentials-file` - 認証情報ファイル（トンネル作成時に生成される）
- `service: https://localhost:443` - Dockerコンテナ内のHTTPSポート
- `noTLSVerify: true` - 自己署名証明書を許可
- `originServerName` - オリジンサーバーの名前（SNI用）

### 5.2 例（実際の値の場合）

```bash
# 本環境での実際の設定
# ユーザー名: b0023035
# トンネルID: 60eb9d8c-3154-4ff8-8192-b78045564bc3

cat > ~/.cloudflared/config.yml << 'EOF'
tunnel: minmi-tunnel
credentials-file: /home/b0023035/.cloudflared/60eb9d8c-3154-4ff8-8192-b78045564bc3.json

ingress:
  - hostname: aws-sample-minmi.click
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: aws-sample-minmi.click
  - service: http_status:404
EOF
```

### 5.3 ファイル確認

```bash
cat ~/.cloudflared/config.yml
```

### 5.4 設定ファイルのパーミッション設定

```bash
chmod 600 ~/.cloudflared/config.yml
```

---

## ステップ6: プロジェクトのセットアップ

### 6.1 リポジトリクローン

```bash
# ホームディレクトリに移動
cd ~

# リポジトリが既にある場合は確認
ls -la /home/b0023035/ProgrammerAptitudeTest

# 既にある場合はそこに移動
cd /home/b0023035/ProgrammerAptitudeTest
```

**本環境では既存：**
```
/home/b0023035/ProgrammerAptitudeTest
```

### 6.2 環境変数ファイル作成

```bash
cp .env.example .env
```

### 6.3 .env ファイル編集

**本環境の実際の設定（.env.production に基づく）：**

```bash
# 重要な設定値を編集

# アプリケーション設定
APP_NAME="ProgrammerAptitudeTest"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aws-sample-minmi.click
APP_PORT=80

# ドメイン設定
SESSION_DOMAIN=aws-sample-minmi.click

# データベース設定（本番環境）
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=programmer_test
DB_USERNAME=programmer_user
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD_123
DB_ROOT_PASSWORD=CHANGE_THIS_ROOT_PASSWORD_456

# Redis設定
REDIS_HOST=redis
REDIS_PASSWORD=
REDIS_PORT=6379

# セッション・キャッシュ設定
SESSION_DRIVER=redis
SESSION_LIFETIME=180
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_DRIVER=redis
CACHE_PREFIX=prog_test_
QUEUE_CONNECTION=redis

# Proxy設定（Cloudflare Tunnel用）
TRUSTED_PROXIES=*

# Sanctum（API認証）
SANCTUM_STATEFUL_DOMAINS=aws-sample-minmi.click,www.aws-sample-minmi.click

# ログ設定
LOG_CHANNEL=stack
LOG_LEVEL=warning

# タイムゾーン
APP_TIMEZONE=Asia/Tokyo
```

**重要な変更点：**
- `DB_PASSWORD` と `DB_ROOT_PASSWORD` は強力なパスワードに変更（CHANGE_THIS...）
- `APP_ENV=production` 必須
- `TRUSTED_PROXIES=*` で Cloudflare経由のIPを認識

### 6.4 APP_KEY生成

```bash
# Docker起動前にAPP_KEYを生成
docker compose -f docker-compose.production.yml up -d

# APP_KEY生成
docker exec programmer-test-app php artisan key:generate --show

# 出力されたキーを .env の APP_KEY に設定
# 例: APP_KEY=base64:xxxxx...
```

### 6.5 Docker Compose起動

```bash
# 本環境での確認
cd /home/b0023035/ProgrammerAptitudeTest

# 本番環境で起動（既に起動している場合はスキップ）
docker compose -f docker-compose.production.yml up -d

# コンテナ状態確認
docker compose -f docker-compose.production.yml ps
```

**期待される出力：**
```
NAME                    IMAGE                        STATUS
programmer-test-app     programmeraptitudetest-app   Up (healthy)
programmer-test-db      mysql:8.0.36                 Up (healthy)
programmer-test-redis   redis:7.2-alpine             Up (healthy)
```

**ポート確認：**
```
PORT                    MAPPING
80                      コンテナのHTTP → ホストのポート80
443                     コンテナのHTTPS → ホストのポート443
3306                    MySQL
6379                    Redis
```

### 6.6 ログ確認

```bash
# アプリケーションログ確認
docker compose -f docker-compose.production.yml logs -f app
```

**ヘルスチェック確認：**
```bash
curl -s http://localhost/health
# 期待される出力: OK
```

---

## ステップ7: Tunnel起動テスト（前準備）

### 7.1 ローカルアクセステスト

```bash
# Tunnel起動前にローカルで動作確認

# HTTP でのアクセス
curl -s http://localhost/ | head -20

# HTTPS でのアクセス（自己署名証明書を許可）
curl -s https://localhost/ -k | head -20

# HTTPステータス確認
curl -s -o /dev/null -w "%{http_code}" http://localhost/
# 期待される出力: 200

# ヘルスチェック確認
curl -s http://localhost/health
# 期待される出力: OK
```

---

## ステップ8: Cloudflare Tunnel起動

### 8.1 現在の設定確認

本環境の設定ファイル：

```bash
# 設定ファイルの内容
cat ~/.cloudflared/config.yml
```

**実際の内容：**
```yaml
tunnel: minmi-tunnel
credentials-file: /home/b0023035/.cloudflared/60eb9d8c-3154-4ff8-8192-b78045564bc3.json

ingress:
  - hostname: aws-sample-minmi.click
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: aws-sample-minmi.click
  - service: http_status:404
```

**設定の説明：**
- `service: https://localhost:443` - Dockerコンテナ内のHTTPSポートに接続
- `noTLSVerify: true` - Dockerの自己署名証明書を許可
- `originServerName` - SNI（Server Name Indication）設定

### 8.2 手動起動（テスト用）

**別のターミナルで以下を実行：**

```bash
# Tunnel起動
cloudflared tunnel run minmi-tunnel
```

**期待される出力：**
```
2024-XX-XXT10:30:00Z INF Starting tunnel minmi-tunnel
2024-XX-XXT10:30:00Z INF Registering tunnel connection from <location>
2024-XX-XXT10:30:00Z INF Tunnel registered successfully
2024-XX-XXT10:30:01Z INF Connected to CDN
```

**このターミナルは実行し続ける状態で保持してください。**

---

## ステップ9: 接続確認

### 9.1 ローカルからのアクセス確認

```bash
# ローカルホストでの確認（元のターミナルで実行）
curl -s https://localhost/ -k | head -20

# HTTPステータス確認
curl -s -o /dev/null -w "%{http_code}" https://localhost/
# 期待される出力: 200

# ヘルスチェック
curl -s http://localhost/health
# 期待される出力: OK
```

### 9.2 Cloudflare Tunnelを通じたアクセス確認

**別のマシンまたは別のターミナルで実行：**

```bash
# Tunnel経由でのアクセス
curl -s https://aws-sample-minmi.click/ | head -20

# HTTPステータス確認
curl -s -o /dev/null -w "%{http_code}" https://aws-sample-minmi.click/
# 期待される出力: 200

# ヘッダー確認（Cloudflareを経由していることを確認）
curl -s -I https://aws-sample-minmi.click/
# 以下のヘッダーが含まれているか確認：
# cf-ray: （Cloudflareが処理していることを示す）
# Server: （アプリケーションのサーバー情報）
```

### 9.3 ブラウザでのアクセス確認

ブラウザで以下のURLにアクセス：
```
https://aws-sample-minmi.click
```

**確認項目：**
- [ ] ページが正常に表示される
- [ ] 鍵アイコンが表示される（HTTPS接続成功）
- [ ] コンソールにエラーが出ていない
- [ ] DBデータが正常に読み込まれている

### 9.4 トラブル時の確認項目

**Tunnel が起動しているか確認：**
```bash
ps aux | grep cloudflared | grep -v grep
# 起動していない場合は ステップ8 を実行
```

**Docker が正常に動作しているか確認：**
```bash
cd /home/b0023035/ProgrammerAptitudeTest
docker compose -f docker-compose.production.yml ps

# すべて "healthy" または "Up" 状態か確認
```

**ポートが正しく開いているか確認：**
```bash
netstat -tlnp | grep -E '80|443|3306|6379'
# またはss コマンド
ss -tlnp | grep -E '80|443|3306|6379'
```

---

## ステップ10: Tunnel バックグラウンド起動（本運用）

### 10.1 バックグラウンド起動方法1（nohup使用）

```bash
# バックグラウンドで起動
nohup cloudflared tunnel run minmi-tunnel > /tmp/cloudflared.log 2>&1 &

# プロセスID確認
jobs -l
ps aux | grep cloudflared

# ログ確認
tail -f /tmp/cloudflared.log

# 停止方法
pkill cloudflared
# または PID指定で終了
kill -9 [PID]
```

**本環境用：**
```bash
# ユーザー b0023035 での実行
cd /home/b0023035
nohup cloudflared tunnel run minmi-tunnel > /tmp/cloudflared.log 2>&1 &

# ログ確認
tail -f /tmp/cloudflared.log
```

### 10.2 バックグラウンド起動方法2（Systemd サービス化）

```bash
# サービスファイル作成（本環境用）
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

# サービス有効化
sudo systemctl enable cloudflared

# サービス起動
sudo systemctl start cloudflared

# サービス状態確認
sudo systemctl status cloudflared

# ログ確認
journalctl -u cloudflared -f

# 停止方法
sudo systemctl stop cloudflared
```

### 10.3 バックグラウンド起動方法3（スクリーン/Tmux使用）

```bash
# screen の場合
screen -S cloudflare -d -m cloudflared tunnel run minmi-tunnel

# 接続確認
screen -ls

# ログ確認
screen -S cloudflare -X stuff "cat /tmp/cloudflared.log^M"
```

---

## トラブルシューティング

### 問題1：「No such file or directory」エラー

```
Error: No such file or directory
  at ~/.cloudflared/config.yml
```

**対策：**
```bash
# 設定ファイルの存在確認
ls -la ~/.cloudflared/config.yml

# 認証情報ファイルの確認
ls -la ~/.cloudflared/*.json

# 置き換えが正しいか確認
cat ~/.cloudflared/config.yml | grep credentials-file
```

### 問題2：「Cannot find tunnel」エラー

```
Error: Cannot find tunnel with ID or name: minmi-tunnel
```

**対策：**
```bash
# トンネル一覧確認
cloudflared tunnel list

# トンネル削除（再作成する場合）
cloudflared tunnel delete minmi-tunnel

# 新しく作成
cloudflared tunnel create minmi-tunnel
```

### 問題3：Tunnelは起動するが接続できない

**確認項目：**

```bash
# 1. Dockerが起動しているか確認
docker compose ps

# 2. ローカルホストへのアクセス確認
curl -s http://localhost/ -k

# 3. config.yml の service: が正しいか確認
# service: http://localhost:80 であることを確認

# 4. ファイアウォール設定確認
sudo ufw status
```

### 問題4：HTTPS/SSL エラー

**例：**
```
error: x509: certificate signed by unknown authority
```

**対策：**

config.yml内で `noTLSVerify: true` を追加（テスト時のみ）

```yaml
ingress:
  - hostname: aws-sample-minmi.click
    service: http://localhost:80
    originRequest:
      noTLSVerify: true  # ← 追加
  - service: http_status:404
```

本番環境ではLet's Encryptで証明書取得を推奨。

### 問題5：「Tunnel is already running」エラー

**対策：**
```bash
# 既存のプロセス確認
ps aux | grep cloudflared

# プロセス終了
pkill cloudflared

# または PID指定で終了
kill -9 [PID]
```

---

## 完了チェックリスト

- [ ] cloudflared インストール完了
- [ ] Cloudflareアカウント認証完了（`~/.cloudflared/cert.pem` 作成）
- [ ] トンネル作成完了（`minmi-tunnel`）
- [ ] DNS設定完了（`cloudflared tunnel route dns`）
- [ ] `~/.cloudflared/config.yml` 作成完了
- [ ] Docker Compose 起動完了
- [ ] ローカルアクセステスト成功
- [ ] Tunnel起動テスト成功
- [ ] https://aws-sample-minmi.click へのアクセス成功
- [ ] バックグラウンド起動設定完了

---

## よく使うコマンド

```bash
# ========================================
# Tunnel 関連コマンド
# ========================================

# Tunnel状態確認
cloudflared tunnel list

# Tunnel起動（前景）
cloudflared tunnel run minmi-tunnel

# Tunnel停止（バックグラウンドの場合）
pkill cloudflared

# ========================================
# ログ確認
# ========================================

# ログ確認（Systemd サービスの場合）
journalctl -u cloudflared -f

# ログ確認（nohup の場合）
tail -f /tmp/cloudflared.log

# ========================================
# Docker 関連コマンド
# ========================================

# コンテナ状態確認
cd /home/b0023035/ProgrammerAptitudeTest
docker compose -f docker-compose.production.yml ps

# Docker ログ確認
docker compose -f docker-compose.production.yml logs -f app

# コンテナ再起動
docker compose -f docker-compose.production.yml restart

# コンテナ停止
docker compose -f docker-compose.production.yml stop

# コンテナ起動
docker compose -f docker-compose.production.yml up -d

# ========================================
# アクセステスト
# ========================================

# ヘルスチェック
curl -s http://localhost/health

# ローカル HTTP アクセス
curl -s http://localhost/ | head -20

# ローカル HTTPS アクセス（自己署名証明書許可）
curl -s https://localhost/ -k | head -20

# リモートアクセス確認
curl -s -I https://aws-sample-minmi.click/

# ========================================
# 設定確認
# ========================================

# Tunnel 設定ファイル確認
cat ~/.cloudflared/config.yml

# 環境変数確認
cat /home/b0023035/ProgrammerAptitudeTest/.env | grep -E "APP_|DB_|REDIS_"

# プロセス確認
ps aux | grep -E "cloudflared|docker"
```

---

## 【重要】本環境の実装状況

### 既に完成している部分

**✅ Cloudflare Tunnel セットアップ完全完成**

- ✅ cloudflared インストール完了（バージョン: 2026.1.2）
- ✅ Cloudflareアカウント認証完了（cert.pem作成済み: /home/b0023035/.cloudflared/cert.pem）
- ✅ トンネル作成完了
  - 名前: **minmi-tunnel**
  - ID: **60eb9d8c-3154-4ff8-8192-b78045564bc3**
  - 作成日時: 2026-01-28T04:46:07Z
  - ステータス: **接続確立中（3つの接続点から接続）**
  
- ✅ DNS設定完了
  - ドメイン: **aws-sample-minmi.click**
  - Cloudflareルーター: CNAME レコード自動作成

- ✅ config.yml 作成完了
  - ファイルパス: **/home/b0023035/.cloudflared/config.yml**
  - サービス接続: **https://localhost:443** （Docker HTTPS）
  - SNI設定: **originServerName: aws-sample-minmi.click**

- ✅ Docker コンテナ起動完了
  - **programmer-test-app**: Up (healthy) - ポート 80, 443
  - **programmer-test-db**: Up (healthy) - MySQL 8.0.36 - ポート 3306
  - **programmer-test-redis**: Up (healthy) - Redis 7.2 - ポート 6379

- ✅ ローカルアクセス確認完了
  - HTTPS接続: 成功（HTTP Status: 200）
  - ヘルスチェック: 動作中

### 最後のステップ：Tunnel起動と外部接続テスト

別のPCからの接続テストが必要です：

**ステップ1：Tunnel起動確認**

```bash
# Tunnelの稼働状態確認
cloudflared tunnel list

# 期待される出力：
# ID: 60eb9d8c-3154-4ff8-8192-b78045564bc3
# NAME: minmi-tunnel
# CONNECTIONS: 1+ （1つ以上の接続が確立）
```

**ステップ2：別PCからのアクセス**

```bash
# Tunnel経由でのリモートアクセステスト
curl -s https://aws-sample-minmi.click/
# または
curl -s -I https://aws-sample-minmi.click/

# ブラウザで確認
# https://aws-sample-minmi.click
```

**ステップ3：接続が成功したら**

```bash
# 本環境でTunnelをバックグラウンド起動
nohup cloudflared tunnel run minmi-tunnel > /tmp/cloudflared.log 2>&1 &

# または Systemd サービス化
sudo systemctl start cloudflared
```

---

## 参考リンク

- [Cloudflare Tunnel公式ドキュメント](https://developers.cloudflare.com/cloudflare-one/connections/connect-applications/)
- [cloudflared CLI リファレンス](https://developers.cloudflare.com/cloudflare-one/connections/connect-applications/install-and-setup/tunnel-guide/local/)
- [Systemd サービス設定ガイド](https://wiki.archlinux.jp/index.php/Systemd#ユーザーサービス)
