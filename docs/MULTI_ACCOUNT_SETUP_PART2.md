# 別のCloudflareアカウント・ドメイン用セットアップ - 第2部

## Nginx設定ファイルの変更

このドキュメントでは、`docker/default.conf` と `docker/default-https.conf` ファイルのドメイン設定を変更する方法を説明します。

---

## 📋 前提条件

以下が完了していることを確認してください：

- [ ] 第1部完了：`.env` ファイルの変更
- [ ] 第1部完了：`.env.production` ファイルの変更
- [ ] 新しいドメインが決定されている
- [ ] Nginx設定ファイルが存在している

---

## 🔍 ステップ1：現在の設定確認

```bash
# Nginx設定ファイルの現在の内容を確認
cd /home/b0023035/ProgrammerAptitudeTest

echo "=== default.conf 確認 ===" && \
grep "aws-sample-minmi.click\|letsencrypt" docker/default.conf && \
echo "" && \
echo "=== default-https.conf 確認 ===" && \
grep "aws-sample-minmi.click\|letsencrypt" docker/default-https.conf
```

**期待される出力**:
```
=== default.conf 確認 ===
    server_name aws-sample-minmi.click www.aws-sample-minmi.click;
    ...
    ssl_certificate /etc/letsencrypt/live/aws-sample-minmi.click/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aws-sample-minmi.click/privkey.pem;

=== default-https.conf 確認 ===
    server_name aws-sample-minmi.click;
    ...
    ssl_certificate /etc/letsencrypt/live/aws-sample-minmi.click/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aws-sample-minmi.click/privkey.pem;
```

---

## 📝 ステップ2：`docker/default.conf` ファイルの変更

### 2.1 ファイルを開く

```bash
# エディタで開く
nano docker/default.conf
# または
vim docker/default.conf
```

### 2.2 変更箇所（計6箇所）

#### 変更箇所1：HTTP server_name（第1ブロック）

**行番号**: 約10行目

```nginx
【変更前】
server {
    listen 80;
    listen [::]:80;
    server_name aws-sample-minmi.click www.aws-sample-minmi.click;

【変更後】
server {
    listen 80;
    listen [::]:80;
    server_name 【新しいドメイン】 www.【新しいドメイン】;
```

**具体例**:
```nginx
server_name myapp.example.com www.myapp.example.com;
```

---

#### 変更箇所2：HTTPS server_name（第2ブロック）

**行番号**: 約27行目

```nginx
【変更前】
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name aws-sample-minmi.click www.aws-sample-minmi.click;

【変更後】
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name 【新しいドメイン】 www.【新しいドメイン】;
```

**具体例**:
```nginx
server_name myapp.example.com www.myapp.example.com;
```

---

#### 変更箇所3：SSL証明書ファイル（fullchain.pem）

**行番号**: 約35行目

```nginx
【変更前】
    ssl_certificate /etc/letsencrypt/live/aws-sample-minmi.click/fullchain.pem;

【変更後】
    ssl_certificate /etc/letsencrypt/live/【新しいドメイン】/fullchain.pem;
```

**具体例**:
```nginx
ssl_certificate /etc/letsencrypt/live/myapp.example.com/fullchain.pem;
```

---

#### 変更箇所4：SSL秘密鍵ファイル（privkey.pem）

**行番号**: 約36行目

```nginx
【変更前】
    ssl_certificate_key /etc/letsencrypt/live/aws-sample-minmi.click/privkey.pem;

【変更後】
    ssl_certificate_key /etc/letsencrypt/live/【新しいドメイン】/privkey.pem;
```

**具体例**:
```nginx
ssl_certificate_key /etc/letsencrypt/live/myapp.example.com/privkey.pem;
```

---

### 2.3 `default.conf` 変更後の確認

```bash
# 変更内容を確認
grep "aws-sample-minmi\|letsencrypt/live" docker/default.conf

# 「aws-sample-minmi」が出現しなければOK
# 新しいドメインが複数行に出現すればOK
```

---

## 📝 ステップ3：`docker/default-https.conf` ファイルの変更

### 3.1 ファイルを開く

```bash
# エディタで開く
nano docker/default-https.conf
# または
vim docker/default-https.conf
```

### 3.2 変更箇所（計4箇所）

#### 変更箇所1：HTTPS server_name（第1ブロック）

**行番号**: 約3行目

```nginx
【変更前】
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name aws-sample-minmi.click;

【変更後】
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name 【新しいドメイン】;
```

**具体例**:
```nginx
server_name myapp.example.com;
```

---

#### 変更箇所2：SSL証明書ファイル（fullchain.pem）

**行番号**: 約16行目

```nginx
【変更前】
    ssl_certificate /etc/letsencrypt/live/aws-sample-minmi.click/fullchain.pem;

【変更後】
    ssl_certificate /etc/letsencrypt/live/【新しいドメイン】/fullchain.pem;
```

**具体例**:
```nginx
ssl_certificate /etc/letsencrypt/live/myapp.example.com/fullchain.pem;
```

---

#### 変更箇所3：SSL秘密鍵ファイル（privkey.pem）

**行番号**: 約17行目

```nginx
【変更前】
    ssl_certificate_key /etc/letsencrypt/live/aws-sample-minmi.click/privkey.pem;

【変更後】
    ssl_certificate_key /etc/letsencrypt/live/【新しいドメイン】/privkey.pem;
```

**具体例**:
```nginx
ssl_certificate_key /etc/letsencrypt/live/myapp.example.com/privkey.pem;
```

---

#### 変更箇所4：HTTPS redirect server_name（第2ブロック）

**行番号**: 約11行目付近（別の server ブロック内）

```nginx
【変更前】
server {
    listen 80;
    listen [::]:80;
    server_name aws-sample-minmi.click;

【変更後】
server {
    listen 80;
    listen [::]:80;
    server_name 【新しいドメイン】;
```

**具体例**:
```nginx
server_name myapp.example.com;
```

---

### 3.3 `default-https.conf` 変更後の確認

```bash
# 変更内容を確認
grep "aws-sample-minmi\|letsencrypt/live" docker/default-https.conf

# 「aws-sample-minmi」が出現しなければOK
# 新しいドメインが複数行に出現すればOK
```

---

## 🔍 全体確認コマンド

両ファイルの変更を確認します：

```bash
# 全体確認
echo "=== 旧ドメインの残存確認 ===" && \
grep -r "aws-sample-minmi.click" docker/default*.conf || echo "✅ 旧ドメインが存在しません（OK）" && \
echo "" && \
echo "=== 新ドメインの設定確認 ===" && \
grep "server_name\|ssl_certificate" docker/default.conf && \
echo "" && \
grep "server_name\|ssl_certificate" docker/default-https.conf
```

---

## 📋 SSL証明書パスの確認

セットアップ前に、SSL証明書が正しく取得できるかどうか確認します：

```bash
# 現在のcertbot設定を確認
ls -la certbot/conf/live/ 2>/dev/null || echo "certbot設定がまだ初期化されていません（初回起動時に自動取得）"

# Let's Encrypt でドメイン認証後、以下のパスに証明書が配置されます：
# /etc/letsencrypt/live/【新しいドメイン】/fullchain.pem
# /etc/letsencrypt/live/【新しいドメイン】/privkey.pem
```

---

## ✅ 第2部完了チェックリスト

- [ ] `docker/default.conf` の HTTP server_name を変更
- [ ] `docker/default.conf` の HTTPS server_name を変更
- [ ] `docker/default.conf` の SSL証明書パスを変更（2箇所）
- [ ] `docker/default-https.conf` のすべてのserver_name を変更（2箇所）
- [ ] `docker/default-https.conf` のSSL証明書パスを変更（2箇所）
- [ ] grep コマンドで旧ドメインが残存していないことを確認
- [ ] 新しいドメインが正しく設定されていることを確認

---

## 🚀 次のステップ

これらのファイルを変更したら、以下の第3部に進みます：

**[MULTI_ACCOUNT_SETUP_PART3.md](MULTI_ACCOUNT_SETUP_PART3.md)** - Cloudflare設定の変更

---

## ⚠️ トラブルシューティング

### 問題1：構文エラーで Docker が起動しない

**原因**: Nginx 設定ファイルに構文エラーがある

**対策**:
```bash
# Docker コンテナを起動
docker compose -f docker-compose.production.yml up -d

# ログでエラー確認
docker compose -f docker-compose.production.yml logs app | grep -E "error|Error|ERROR"

# Nginx の設定ファイルを validate
docker exec programmer-test-app nginx -t
```

### 問題2：SSL証明書パスが間違っている

**原因**: パス内のドメイン名が誤記されている

**対策**:
```bash
# 現在のディレクトリ構造を確認
ls -la certbot/conf/live/

# ファイルパスが正しいか確認
grep "ssl_certificate" docker/default*.conf
```

---

**注意**: このドキュメントではファイルを実際には編集していません。手動でファイルを編集する必要があります。
