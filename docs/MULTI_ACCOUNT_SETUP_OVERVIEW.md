# 別のCloudflareアカウント・ドメイン用セットアップガイド

## 📋 【ステップ0】変更が必要なファイル一覧と箇所

このドキュメントは、別のCloudflareアカウントで同じプロジェクトをセットアップする際の**ファイル変更箇所**をまとめています。

**注意**: ファイルの変更は自分で行う必要があります。本ドキュメントではファイルを編集しません。

---

## 📌 変更情報の準備

セットアップ前に、以下の情報を用意してください：

```
【新しい Cloudflare アカウント用情報】
- 新しいCloudflareアカウント名（メールアドレス）: _________________________
- 新しいドメイン: _________________________
- 新しいトンネル名: _________________________（例: my-tunnel）

【新しいサーバー情報】
- 新しいユーザー名: _________________________
- 新しいホームディレクトリ: /home/_________________________
- DB名: _________________________（例: myapp_db）
- DBユーザー: _________________________（例: myapp_user）
```

---

## 🔧 ファイル変更リスト

### グループ1：ドメイン関連ファイル（最優先）

#### 1.1 `.env` ファイル

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/.env`

**変更対象行** (3箇所):

```env
【変更前】
APP_URL=https://aws-sample-minmi.click
SESSION_DOMAIN=aws-sample-minmi.click
MAIL_FROM_ADDRESS="noreply@aws-sample-minmi.click"

【変更後】
APP_URL=https://【新しいドメイン】
SESSION_DOMAIN=【新しいドメイン】
MAIL_FROM_ADDRESS="noreply@【新しいドメイン】"
```

**具体例**:
```
APP_URL=https://myapp.example.com
SESSION_DOMAIN=myapp.example.com
MAIL_FROM_ADDRESS="noreply@myapp.example.com"
```

---

#### 1.2 `.env.production` ファイル

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/.env.production`

**変更対象行** (3箇所):

```env
【変更前】
APP_URL=https://aws-sample-minmi.click
SESSION_DOMAIN=aws-sample-minmi.click
SANCTUM_STATEFUL_DOMAINS=aws-sample-minmi.click,www.aws-sample-minmi.click

【変更後】
APP_URL=https://【新しいドメイン】
SESSION_DOMAIN=【新しいドメイン】
SANCTUM_STATEFUL_DOMAINS=【新しいドメイン】,www.【新しいドメイン】
```

**具体例**:
```
APP_URL=https://myapp.example.com
SESSION_DOMAIN=myapp.example.com
SANCTUM_STATEFUL_DOMAINS=myapp.example.com,www.myapp.example.com
```

---

### グループ2：Nginx設定ファイル（次優先）

#### 2.1 `docker/default.conf` ファイル

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/docker/default.conf`

**変更対象行** (6箇所):

```nginx
【変更前】
server_name aws-sample-minmi.click www.aws-sample-minmi.click;
...
ssl_certificate /etc/letsencrypt/live/aws-sample-minmi.click/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/aws-sample-minmi.click/privkey.pem;

【変更後】
server_name 【新しいドメイン】 www.【新しいドメイン】;
...
ssl_certificate /etc/letsencrypt/live/【新しいドメイン】/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/【新しいドメイン】/privkey.pem;
```

**具体例**:
```
server_name myapp.example.com www.myapp.example.com;
...
ssl_certificate /etc/letsencrypt/live/myapp.example.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/myapp.example.com/privkey.pem;
```

---

#### 2.2 `docker/default-https.conf` ファイル

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/docker/default-https.conf`

**変更対象行** (4箇所):

```nginx
【変更前】
server_name aws-sample-minmi.click;
...
ssl_certificate /etc/letsencrypt/live/aws-sample-minmi.click/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/aws-sample-minmi.click/privkey.pem;

【変更後】
server_name 【新しいドメイン】;
...
ssl_certificate /etc/letsencrypt/live/【新しいドメイン】/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/【新しいドメイン】/privkey.pem;
```

**具体例**:
```
server_name myapp.example.com;
...
ssl_certificate /etc/letsencrypt/live/myapp.example.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/myapp.example.com/privkey.pem;
```

---

### グループ3：Cloudflare設定ファイル（重要）

#### 3.1 `~/.cloudflared/config.yml` ファイル

**ファイルパス**: `/home/【新しいユーザー名】/.cloudflared/config.yml`

**変更内容**:

```yaml
【変更前】
tunnel: minmi-tunnel
credentials-file: /home/b0023035/.cloudflared/60eb9d8c-3154-4ff8-8192-b78045564bc3.json

ingress:
  - hostname: aws-sample-minmi.click
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: aws-sample-minmi.click
  - service: http_status:404

【変更後】
tunnel: 【新しいトンネル名】
credentials-file: /home/【新しいユーザー名】/.cloudflared/【新しいトンネルID】.json

ingress:
  - hostname: 【新しいドメイン】
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: 【新しいドメイン】
  - service: http_status:404
```

**具体例**:
```yaml
tunnel: my-tunnel
credentials-file: /home/newuser/.cloudflared/a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d.json

ingress:
  - hostname: myapp.example.com
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: myapp.example.com
  - service: http_status:404
```

---

### グループ4：データベース設定（パスワード変更推奨）

#### 4.1 `.env` ファイル（DB関連）

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/.env`

**変更対象行**（セキュリティ上、変更を強く推奨）:

```env
【変更前】
DB_PASSWORD=password
DB_ROOT_PASSWORD=rootpassword

【変更後】
DB_PASSWORD=【強力な新しいパスワード】
DB_ROOT_PASSWORD=【強力な新しいルートパスワード】
```

---

#### 4.2 `.env.production` ファイル（DB関連）

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/.env.production`

**変更対象行**:

```env
【変更前】
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD_123
DB_ROOT_PASSWORD=CHANGE_THIS_ROOT_PASSWORD_456
DB_DATABASE=programmer_test
DB_USERNAME=programmer_user

【変更後】
DB_PASSWORD=【強力な新しいパスワード】
DB_ROOT_PASSWORD=【強力な新しいルートパスワード】
DB_DATABASE=【新しいDB名】
DB_USERNAME=【新しいDBユーザー】
```

---

### グループ5：Docker設定ファイル（オプション）

#### 5.1 `docker-compose.production.yml` ファイル

**ファイルパス**: `/home/[ユーザー名]/ProgrammerAptitudeTest/docker-compose.production.yml`

**変更対象行**（環境変数セクション）:

```yaml
【変更前】
environment:
    - APP_KEY=${APP_KEY}
    - APP_ENV=production
    - APP_DEBUG=false
    - APP_URL=${APP_URL:-http://localhost}
    - DB_CONNECTION=mysql
    - DB_HOST=db
    - DB_PORT=3306
    - DB_DATABASE=${DB_DATABASE:-laravel}
    - DB_USERNAME=${DB_USERNAME:-sail}
    - DB_PASSWORD=${DB_PASSWORD:-password}

【変更後】
# .env ファイルから自動的に読み込まれるため、この部分の変更は不要
# ただし、DB_DATABASE と DB_USERNAME をカスタマイズした場合は確認
```

**備考**: このファイルは `.env` から環境変数を自動的に読み込むため、基本的には変更不要です。

---

## 📑 変更ファイル優先度リスト

| 優先度 | ファイル | 変更箇所数 | 必須 |
|--------|---------|----------|------|
| 🔴 1 | `.env` | 3 | ✅ |
| 🔴 1 | `.env.production` | 3 | ✅ |
| 🟠 2 | `docker/default.conf` | 6 | ✅ |
| 🟠 2 | `docker/default-https.conf` | 4 | ✅ |
| 🟠 2 | `~/.cloudflared/config.yml` | 3 | ✅ |
| 🟡 3 | `.env` (DB) | 2 | ⚠️ (推奨) |
| 🟡 3 | `.env.production` (DB) | 4 | ⚠️ (推奨) |
| 🟢 4 | `docker-compose.production.yml` | 0 | ❌ |

---

## ✅ 変更確認チェックリスト

### ドメイン関連

- [ ] `.env` のドメイン3箇所を変更
- [ ] `.env.production` のドメイン3箇所を変更
- [ ] `docker/default.conf` のドメイン6箇所を変更
- [ ] `docker/default-https.conf` のドメイン4箇所を変更
- [ ] `~/.cloudflared/config.yml` のドメイン3箇所を変更

### Cloudflare Tunnel関連

- [ ] `~/.cloudflared/config.yml` でトンネル名を変更
- [ ] `~/.cloudflared/config.yml` でトンネルID（credentials-file）を変更
- [ ] `~/.cloudflared/config.yml` でユーザー名パスを変更

### データベース関連（推奨）

- [ ] `.env` のDBパスワード2箇所を変更
- [ ] `.env.production` のDBパスワード・ユーザー・DB名を変更

---

## 🚀 次のステップ

次のドキュメントで、段階的な変更手順を説明しています：

1. **[MULTI_ACCOUNT_SETUP_PART1.md](MULTI_ACCOUNT_SETUP_PART1.md)** - 第1部：.envファイル変更
2. **[MULTI_ACCOUNT_SETUP_PART2.md](MULTI_ACCOUNT_SETUP_PART2.md)** - 第2部：Nginx設定変更
3. **[MULTI_ACCOUNT_SETUP_PART3.md](MULTI_ACCOUNT_SETUP_PART3.md)** - 第3部：Cloudflare設定変更
4. **[MULTI_ACCOUNT_SETUP_PART4.md](MULTI_ACCOUNT_SETUP_PART4.md)** - 第4部：検証とトラブルシューティング

---

**注意**: このドキュメントでは実際のファイルは変更していません。各ファイルの変更は自分で行う必要があります。
