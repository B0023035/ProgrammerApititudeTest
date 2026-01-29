# 他のPC・Cloudflareアカウントでの移行ガイド

このシステムを別のPC環境や異なるCloudflareアカウントで動作させる際に変更が必要な箇所をまとめます。

---

## 変更が必要なファイル一覧

| ファイル | 変更理由 |
|----------|----------|
| `.env` | ドメイン、DB認証情報、APP_KEY等 |
| `~/.cloudflared/config.yml` | トンネルID、認証情報 |
| `docker/default.conf` | ドメイン名 |
| `certbot/conf/live/[domain]/` | SSL証明書 |

---

## 1. .env ファイルの変更

### 変更が必要な項目

```bash
# ==========================================
# 必ず変更が必要
# ==========================================

# 新しいキーを生成（セキュリティ上必須）
APP_KEY=base64:新しいキーを生成

# 新しいドメインに変更
APP_URL=https://新しいドメイン.com

# セッションドメインを変更
SESSION_DOMAIN=新しいドメイン.com

# ==========================================
# 必要に応じて変更
# ==========================================

# データベースパスワード（セキュリティ向上のため変更推奨）
DB_PASSWORD=新しいパスワード

# メール設定（パスワードリセット機能を使う場合）
MAIL_USERNAME=新しいメールアドレス
MAIL_PASSWORD=新しいアプリパスワード
MAIL_FROM_ADDRESS=noreply@新しいドメイン.com
```

### APP_KEY の生成方法

```bash
# 方法1: PHPがインストールされている場合
php artisan key:generate --show

# 方法2: Dockerコンテナ内で生成
docker compose -f docker-compose.production.yml up -d
docker exec programmer-test-app php artisan key:generate --show
```

生成されたキーを `.env` の `APP_KEY=` に設定。

---

## 2. Cloudflare Tunnel の再設定

### 2.1 新しいアカウントでログイン

```bash
# 既存の認証情報を削除
rm -rf ~/.cloudflared/

# 新しいアカウントでログイン
cloudflared tunnel login
```

### 2.2 新しいトンネルを作成

```bash
# トンネル作成
cloudflared tunnel create [新しいトンネル名]

# 出力されるトンネルIDをメモ
# 例: Created tunnel your-tunnel with id xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

### 2.3 DNS設定

```bash
# Cloudflare DNSにルートを追加
cloudflared tunnel route dns [トンネル名] [新しいドメイン]
```

### 2.4 config.yml の作成

`~/.cloudflared/config.yml`:

```yaml
# ==========================================
# 変更が必要な箇所
# ==========================================
tunnel: 新しいトンネルID
credentials-file: /home/新しいユーザー名/.cloudflared/新しいトンネルID.json

ingress:
  - hostname: 新しいドメイン.com        # ← 変更
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
  - service: http_status:404
```

---

## 3. SSL証明書の取得

### Cloudflare Origin CA証明書

1. **Cloudflareダッシュボードにログイン**
   - https://dash.cloudflare.com/

2. **対象ドメインを選択**

3. **SSL/TLS → Origin Server へ移動**

4. **「Create Certificate」をクリック**

5. **設定**:
   - Private key type: RSA (2048)
   - Hostnames: 
     - `新しいドメイン.com`
     - `*.新しいドメイン.com`
   - Certificate Validity: 15 years

6. **「Create」をクリック**

7. **証明書を保存**

```bash
# ディレクトリ作成
mkdir -p certbot/conf/live/新しいドメイン.com/

# fullchain.pem: Origin Certificate（画面からコピー）
# privkey.pem: Private Key（画面からコピー）
```

---

## 4. Nginx設定の変更

### docker/default.conf

```nginx
# ==========================================
# 変更が必要な箇所（2箇所）
# ==========================================

# HTTPサーバーブロック
server {
    listen 80;
    listen [::]:80;
    server_name 新しいドメイン.com www.新しいドメイン.com;  # ← 変更
    # ...
}

# HTTPSサーバーブロック  
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name 新しいドメイン.com www.新しいドメイン.com;  # ← 変更
    
    # SSL証明書パス（ドメイン名が含まれる）
    ssl_certificate /etc/letsencrypt/live/新しいドメイン.com/fullchain.pem;      # ← 変更
    ssl_certificate_key /etc/letsencrypt/live/新しいドメイン.com/privkey.pem;    # ← 変更
    # ...
}
```

---

## 5. docker-compose.production.yml の確認

通常は変更不要ですが、以下を確認：

```yaml
services:
  app:
    volumes:
      # 証明書のパスがドメイン名に依存
      - ./certbot/conf:/etc/letsencrypt:ro
```

証明書フォルダ構造:
```
certbot/
└── conf/
    └── live/
        └── 新しいドメイン.com/    # ← フォルダ名を変更
            ├── fullchain.pem
            └── privkey.pem
```

---

## 6. 移行手順チェックリスト

### 準備段階

- [ ] 新しいPCにDockerをインストール
- [ ] 新しいPCにcloudflaredをインストール
- [ ] 新しいPCにNode.jsをインストール
- [ ] Cloudflareアカウントを用意（ドメインを追加済み）

### ファイル移行

- [ ] プロジェクトフォルダをコピー
- [ ] `.env` を編集（APP_KEY、APP_URL、SESSION_DOMAIN）
- [ ] `docker/default.conf` のドメイン名を変更

### Cloudflare設定

- [ ] `cloudflared tunnel login` で認証
- [ ] `cloudflared tunnel create` でトンネル作成
- [ ] `cloudflared tunnel route dns` でDNS設定
- [ ] `~/.cloudflared/config.yml` を作成

### SSL証明書

- [ ] Cloudflareで Origin CA証明書を作成
- [ ] `certbot/conf/live/[新ドメイン]/` に証明書を保存

### 起動・確認

- [ ] `npm install && npm run build` でフロントエンドビルド
- [ ] `docker compose -f docker-compose.production.yml build`
- [ ] `docker compose -f docker-compose.production.yml up -d`
- [ ] `docker exec programmer-test-app php artisan migrate --force`
- [ ] データベースリストア（必要な場合）
- [ ] `cloudflared tunnel run [tunnel-name]` でトンネル起動
- [ ] ブラウザでアクセス確認

---

## 7. 変更箇所の早見表

### 検索・置換用

| 旧値 | 新値 | 対象ファイル |
|------|------|-------------|
| `aws-sample-minmi.click` | `新しいドメイン.com` | `.env`, `docker/default.conf` |
| `60eb9d8c-3154-4ff8-8192-b78045564bc3` | `新しいトンネルID` | `~/.cloudflared/config.yml` |
| `minmi-tunnel` | `新しいトンネル名` | cloudflaredコマンド |
| `/home/b0023035/` | `/home/新しいユーザー/` | `~/.cloudflared/config.yml` |

### 一括置換コマンド（参考）

```bash
# .envファイル内のドメイン置換
sed -i 's/aws-sample-minmi.click/新しいドメイン.com/g' .env

# Nginx設定内のドメイン置換
sed -i 's/aws-sample-minmi.click/新しいドメイン.com/g' docker/default.conf
```

---

## 8. データ移行

### データベースのエクスポート（元の環境）

```bash
docker exec programmer-test-db mysqldump -u sail -ppassword laravel > laravel_backup.sql
```

### データベースのインポート（新しい環境）

```bash
# コンテナ起動後
docker exec -i programmer-test-db mysql -u sail -ppassword laravel < laravel_backup.sql
```

### ファイルアップロードの移行

```bash
# storageフォルダをコピー
cp -r storage/app/public/* /新しい環境/storage/app/public/
```

---

## 9. 注意事項

### セキュリティ

1. **APP_KEY は必ず新しく生成**
   - 元の環境のキーを使い回さない
   - キーが漏洩するとセッションハイジャックのリスク

2. **パスワードの変更を推奨**
   - DB_PASSWORD
   - 管理者パスワード
   - MAIL_PASSWORD

### 互換性

1. **PHPバージョン**: 8.3以上
2. **Node.jsバージョン**: 18以上
3. **MySQLバージョン**: 8.0

### Cloudflare

1. **ドメインのネームサーバー**: Cloudflareに設定済みであること
2. **SSL/TLS設定**: Full (strict) を推奨
3. **プロキシ**: オレンジ色のクラウドがオンになっていること
