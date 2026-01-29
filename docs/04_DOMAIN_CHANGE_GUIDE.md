# ドメイン変更ガイド

## 現在のドメイン設定

- **ドメイン**: `aws-sample-minmi.click`
- **Cloudflare経由**: 有効

> ⚠️ このドメインは将来的に置き換え予定です。変更時は以下の箇所を更新してください。

---

## ドメイン変更時に編集が必要なファイル

### 1. `.env` ファイル（プロジェクトルート）

```env
# 8行目付近
APP_URL=https://aws-sample-minmi.click

# 新しいドメインに変更
APP_URL=https://新しいドメイン
```

**場所**: `/home/b0023035/ProgrammerAptitudeTest/.env`

---

### 2. `docker-compose.prod-test.yml`

#### 2.1 ビルド引数（16行目付近）

```yaml
build:
    context: .
    dockerfile: Dockerfile.prod-simple
    args:
        APP_URL: https://aws-sample-minmi.click # ← ここを変更
```

#### 2.2 環境変数（25行目付近）

```yaml
environment:
    - APP_URL=https://aws-sample-minmi.click # ← ここを変更
```

**場所**: `/home/b0023035/ProgrammerAptitudeTest/docker-compose.prod-test.yml`

---

### 3. Cloudflare設定（外部）

Cloudflareダッシュボードで以下を更新：

1. **DNS設定**
    - Aレコード: 新しいドメイン → サーバーIP
    - CNAMEレコード: www → 新しいドメイン

2. **SSL/TLS設定**
    - フル（厳密）モードを推奨

---

## 変更後の再構築手順

```bash
cd /home/b0023035/ProgrammerAptitudeTest

# 1. コンテナ停止
docker compose -f docker-compose.prod-test.yml down

# 2. イメージ再構築（キャッシュなし）
docker compose -f docker-compose.prod-test.yml build --no-cache prod-app

# 3. コンテナ起動
docker compose -f docker-compose.prod-test.yml up -d

# 4. キャッシュクリア
docker exec prog-test-prod-app php artisan config:clear
docker exec prog-test-prod-app php artisan cache:clear
docker exec prog-test-prod-app php artisan config:cache
```

---

## Cloudflare特有の設定

### TrustedProxies設定

Cloudflare経由のアクセスでは、実際のクライアントIPを取得するために`TrustProxies`ミドルウェアの設定が必要です。

**ファイル**: `app/Http/Middleware/TrustProxies.php`

```php
protected $proxies = '*';  // Cloudflareからのリクエストを信頼

protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO |
    Request::HEADER_X_FORWARDED_AWS_ELB;
```

### セッション設定

HTTPS使用時は`.env`で以下を設定：

```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

---

## チェックリスト

ドメイン変更時に確認する項目：

- [ ] `.env` の `APP_URL` を更新
- [ ] `docker-compose.prod-test.yml` のビルド引数を更新
- [ ] `docker-compose.prod-test.yml` の環境変数を更新
- [ ] Cloudflare DNSレコードを更新
- [ ] SSL証明書が有効か確認
- [ ] コンテナを再構築
- [ ] キャッシュをクリア
- [ ] アクセステストを実施

---

## 関連ファイル一覧

| ファイル                               | 変更箇所            | 説明                          |
| -------------------------------------- | ------------------- | ----------------------------- |
| `.env`                                 | APP_URL             | アプリケーションURL           |
| `docker-compose.prod-test.yml`         | args.APP_URL        | ビルド時URL                   |
| `docker-compose.prod-test.yml`         | environment.APP_URL | 実行時URL                     |
| `config/app.php`                       | url                 | APP_URLを参照（通常変更不要） |
| `app/Http/Middleware/TrustProxies.php` | proxies             | プロキシ信頼設定              |
