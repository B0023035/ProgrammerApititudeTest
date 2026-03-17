# 別のCloudflareアカウント・ドメイン用セットアップ - 第1部

## `.env` ファイルと `.env.production` ファイルの変更

このドキュメントでは、ドメインとCloudflareアカウントを変更するための最初のステップである、環境変数ファイルの変更方法を説明します。

---

## 📋 変更前の確認

まず、現在の設定値を確認します。

```bash
# 現在のプロジェクトディレクトリに移動
cd /home/b0023035/ProgrammerAptitudeTest

# 現在の .env ファイルを確認
cat .env | grep -E "APP_URL|SESSION_DOMAIN|MAIL_FROM"

# 現在の .env.production ファイルを確認
cat .env.production | grep -E "APP_URL|SESSION_DOMAIN|MAIL_FROM|SANCTUM|DB_"
```

**期待される出力**:
```
APP_URL=https://aws-sample-minmi.click
SESSION_DOMAIN=aws-sample-minmi.click
MAIL_FROM_ADDRESS="noreply@aws-sample-minmi.click"
SANCTUM_STATEFUL_DOMAINS=aws-sample-minmi.click,www.aws-sample-minmi.click
DB_DATABASE=programmer_test
DB_USERNAME=programmer_user
```

---

## 🔧 ステップ1：新しいドメイン情報の準備

以下の情報を決定してください。この情報を変更時に使用します：

```
【変更情報】
新しいドメイン: _________________________ （例: myapp.example.com）
新しいDB名: _________________________ （例: myapp_db）
新しいDBユーザー: _________________________ （例: myapp_user）
新しいDBパスワード: _________________________ （強力なパスワード）
```

---

## 📝 ステップ2：`.env` ファイルの変更

### 2.1 `.env` ファイルを開く

```bash
# エディタで開く（nano の例）
nano .env

# または vim
vim .env

# または VS Code
code .env
```

### 2.2 変更箇所（3箇所）

#### 変更箇所1：`APP_URL`

**行番号**: 約8行目

```env
【変更前】
APP_URL=https://aws-sample-minmi.click

【変更後】
APP_URL=https://【新しいドメイン】
```

**具体例**:
```env
APP_URL=https://myapp.example.com
```

---

#### 変更箇所2：`SESSION_DOMAIN`

**行番号**: 約25行目

```env
【変更前】
SESSION_DOMAIN=aws-sample-minmi.click

【変更後】
SESSION_DOMAIN=【新しいドメイン】
```

**具体例**:
```env
SESSION_DOMAIN=myapp.example.com
```

---

#### 変更箇所3：`MAIL_FROM_ADDRESS`

**行番号**: 約47行目

```env
【変更前】
MAIL_FROM_ADDRESS="noreply@aws-sample-minmi.click"

【変更後】
MAIL_FROM_ADDRESS="noreply@【新しいドメイン】"
```

**具体例**:
```env
MAIL_FROM_ADDRESS="noreply@myapp.example.com"
```

---

### 2.3 `.env` ファイル変更後の確認

```bash
# 変更内容を確認
cat .env | grep -E "APP_URL|SESSION_DOMAIN|MAIL_FROM"

# 期待される出力：
# APP_URL=https://myapp.example.com
# SESSION_DOMAIN=myapp.example.com
# MAIL_FROM_ADDRESS="noreply@myapp.example.com"
```

---

## 📝 ステップ3：`.env.production` ファイルの変更

### 3.1 `.env.production` ファイルを開く

```bash
# エディタで開く
nano .env.production
# または
vim .env.production
```

### 3.2 変更箇所（計4箇所）

#### 変更箇所1：`APP_URL`

**行番号**: 約11行目

```env
【変更前】
APP_URL=https://aws-sample-minmi.click

【変更後】
APP_URL=https://【新しいドメイン】
```

**具体例**:
```env
APP_URL=https://myapp.example.com
```

---

#### 変更箇所2：`SESSION_DOMAIN`

**行番号**: 約39行目

```env
【変更前】
SESSION_DOMAIN=aws-sample-minmi.click

【変更後】
SESSION_DOMAIN=【新しいドメイン】
```

**具体例**:
```env
SESSION_DOMAIN=myapp.example.com
```

---

#### 変更箇所3：`SANCTUM_STATEFUL_DOMAINS`

**行番号**: 約62行目

```env
【変更前】
SANCTUM_STATEFUL_DOMAINS=aws-sample-minmi.click,www.aws-sample-minmi.click

【変更後】
SANCTUM_STATEFUL_DOMAINS=【新しいドメイン】,www.【新しいドメイン】
```

**具体例**:
```env
SANCTUM_STATEFUL_DOMAINS=myapp.example.com,www.myapp.example.com
```

---

#### 変更箇所4：データベース設定（推奨：セキュリティのため強力なパスワードに変更）

**行番号**: 約16-23行目

```env
【変更前】
DB_DATABASE=programmer_test
DB_USERNAME=programmer_user
DB_PASSWORD=CHANGE_THIS_STRONG_PASSWORD_123
DB_ROOT_PASSWORD=CHANGE_THIS_ROOT_PASSWORD_456

【変更後】
DB_DATABASE=【新しいDB名】
DB_USERNAME=【新しいDBユーザー】
DB_PASSWORD=【強力な新しいパスワード】
DB_ROOT_PASSWORD=【強力な新しいルートパスワード】
```

**具体例**:
```env
DB_DATABASE=myapp_db
DB_USERNAME=myapp_user
DB_PASSWORD=MyStr0ng!Pass@2024#SecureDB
DB_ROOT_PASSWORD=RootPass!2024#MyApp@Secure
```

**パスワード生成のコツ**:
```bash
# 強力なパスワードを生成
openssl rand -base64 12

# または
date +%s | sha256sum | base64 | head -c 32
```

---

### 3.3 `.env.production` ファイル変更後の確認

```bash
# 変更内容を確認
cat .env.production | grep -E "APP_URL|SESSION_DOMAIN|SANCTUM|DB_"

# 期待される出力（例）：
# APP_URL=https://myapp.example.com
# SESSION_DOMAIN=myapp.example.com
# SANCTUM_STATEFUL_DOMAINS=myapp.example.com,www.myapp.example.com
# DB_DATABASE=myapp_db
# DB_USERNAME=myapp_user
```

---

## 🔍 全体確認コマンド

変更が完了したら、以下のコマンドで確認します：

```bash
# .env ファイルの全体確認
echo "=== .env ファイル確認 ===" && \
grep -E "^APP_URL=|^SESSION_DOMAIN=|^MAIL_FROM_ADDRESS=" .env && \
echo "" && \
echo "=== .env.production ファイル確認 ===" && \
grep -E "^APP_URL=|^SESSION_DOMAIN=|^SANCTUM_STATEFUL_DOMAINS=|^DB_DATABASE=|^DB_USERNAME=" .env.production
```

**期待される出力（例）**:
```
=== .env ファイル確認 ===
APP_URL=https://myapp.example.com
SESSION_DOMAIN=myapp.example.com
MAIL_FROM_ADDRESS="noreply@myapp.example.com"

=== .env.production ファイル確認 ===
APP_URL=https://myapp.example.com
SESSION_DOMAIN=myapp.example.com
SANCTUM_STATEFUL_DOMAINS=myapp.example.com,www.myapp.example.com
DB_DATABASE=myapp_db
DB_USERNAME=myapp_user
```

---

## ✅ 第1部完了チェックリスト

- [ ] `.env` ファイルの `APP_URL` を変更
- [ ] `.env` ファイルの `SESSION_DOMAIN` を変更
- [ ] `.env` ファイルの `MAIL_FROM_ADDRESS` を変更
- [ ] `.env.production` ファイルの `APP_URL` を変更
- [ ] `.env.production` ファイルの `SESSION_DOMAIN` を変更
- [ ] `.env.production` ファイルの `SANCTUM_STATEFUL_DOMAINS` を変更
- [ ] `.env.production` ファイルの DB設定を変更（推奨）
- [ ] 全ての変更が正しいことを確認（grep コマンドで確認）

---

## 🚀 次のステップ

これらのファイルを変更したら、以下の第2部に進みます：

**[MULTI_ACCOUNT_SETUP_PART2.md](MULTI_ACCOUNT_SETUP_PART2.md)** - Nginx設定ファイルの変更

---

## ⚠️ トラブルシューティング

### 問題1：ファイルを保存できない

**原因**: エディタでのセーブ失敗

**対策**:
```bash
# ファイルのパーミッション確認
ls -la .env .env.production

# パーミッション修正
chmod 644 .env .env.production
```

### 問題2：変更内容が反映されない

**原因**: Docker コンテナがキャッシュを保持している

**対策**:
```bash
# Docker コンテナを再起動
docker compose -f docker-compose.production.yml restart

# または
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml up -d
```

---

**注意**: このドキュメントではファイルを実際には編集していません。手動でファイルを編集する必要があります。
