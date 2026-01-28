# 環境設定マニュアル

## 1. 環境変数一覧

### 1.1 基本設定

| 変数名    | 説明                   | 例                             |
| --------- | ---------------------- | ------------------------------ |
| APP_NAME  | アプリケーション名     | "Programmer Aptitude Test"     |
| APP_ENV   | 環境種別               | local/staging/production       |
| APP_KEY   | 暗号化キー（自動生成） | base64:...                     |
| APP_DEBUG | デバッグモード         | true/false                     |
| APP_URL   | アプリケーションURL    | https://aws-sample-minmi.click |

### 1.2 データベース設定

| 変数名        | 説明             | 例                      |
| ------------- | ---------------- | ----------------------- |
| DB_CONNECTION | データベース種別 | mysql                   |
| DB_HOST       | ホスト名         | db (Docker) / localhost |
| DB_PORT       | ポート番号       | 3306                    |
| DB_DATABASE   | データベース名   | laravel                 |
| DB_USERNAME   | ユーザー名       | laravel                 |
| DB_PASSWORD   | パスワード       | secure_password         |

### 1.3 Redis設定

| 変数名         | 説明        | 例                         |
| -------------- | ----------- | -------------------------- |
| REDIS_HOST     | Redisホスト | redis (Docker) / 127.0.0.1 |
| REDIS_PASSWORD | パスワード  | null                       |
| REDIS_PORT     | ポート番号  | 6379                       |

### 1.4 セッション・キャッシュ設定

| 変数名                | 説明                   | 例                  |
| --------------------- | ---------------------- | ------------------- |
| SESSION_DRIVER        | セッションドライバー   | redis/file/database |
| SESSION_LIFETIME      | セッション有効期間(分) | 120                 |
| SESSION_SECURE_COOKIE | HTTPS専用Cookie        | true (本番)         |
| CACHE_DRIVER          | キャッシュドライバー   | redis/file          |

### 1.5 メール設定

| 変数名            | 説明             | 例                  |
| ----------------- | ---------------- | ------------------- |
| MAIL_MAILER       | メールドライバー | smtp/log            |
| MAIL_HOST         | SMTPホスト       | smtp.gmail.com      |
| MAIL_PORT         | SMTPポート       | 587                 |
| MAIL_USERNAME     | SMTPユーザー     | your@email.com      |
| MAIL_PASSWORD     | SMTPパスワード   | app_password        |
| MAIL_ENCRYPTION   | 暗号化方式       | tls                 |
| MAIL_FROM_ADDRESS | 送信元アドレス   | noreply@example.com |
| MAIL_FROM_NAME    | 送信者名         | "Programmer Test"   |

---

## 2. 環境別設定

### 2.1 開発環境（.env.local）

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1

SESSION_DRIVER=file
CACHE_DRIVER=file
SESSION_SECURE_COOKIE=false

MAIL_MAILER=log
```

### 2.2 ステージング環境（.env.staging）

```env
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.your-domain.com

DB_HOST=db

SESSION_DRIVER=redis
CACHE_DRIVER=redis
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
```

### 2.3 本番環境（.env.production）

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aws-sample-minmi.click

DB_HOST=db

SESSION_DRIVER=redis
CACHE_DRIVER=redis
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp

# Cloudflare対応
TRUSTED_PROXIES=*
```

---

## 3. Docker Compose設定

### 3.1 開発用（docker-compose.yml）

```yaml
services:
    app:
        build: .
        ports:
            - "8000:80"
        volumes:
            - .:/var/www/html
        environment:
            - APP_ENV=local
```

### 3.2 本番用（docker-compose.prod.yml）

```yaml
services:
    app:
        build:
            context: .
            args:
                - APP_URL=https://aws-sample-minmi.click
        ports:
            - "80:80"
        environment:
            - APP_ENV=production
            - APP_DEBUG=false
```

---

## 4. Cloudflare設定

### 4.1 DNS設定

| タイプ | 名前 | 値         | プロキシ |
| ------ | ---- | ---------- | -------- |
| A      | @    | サーバーIP | 有効     |
| A      | www  | サーバーIP | 有効     |

### 4.2 SSL/TLS設定

- モード: Full (strict)
- 最小TLSバージョン: 1.2
- 自動HTTPS書き換え: 有効

### 4.3 Laravel側の対応

`bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

---

## 5. パフォーマンス最適化

### 5.1 本番環境用キャッシュコマンド

```bash
# 設定キャッシュ
php artisan config:cache

# ルートキャッシュ
php artisan route:cache

# ビューキャッシュ
php artisan view:cache

# オートローダー最適化
composer install --optimize-autoloader --no-dev
```

### 5.2 キャッシュクリア

```bash
php artisan optimize:clear
```

---

## 6. ログ設定

### 6.1 ログレベル

| レベル    | 説明                   |
| --------- | ---------------------- |
| emergency | システムが使用不能     |
| alert     | 即座の対応が必要       |
| critical  | 重大なエラー           |
| error     | エラー                 |
| warning   | 警告                   |
| notice    | 通常だが重要なイベント |
| info      | 情報                   |
| debug     | デバッグ情報           |

### 6.2 設定例（config/logging.php）

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

---

## 7. セキュリティ設定

### 7.1 本番環境チェックリスト

- [ ] APP_DEBUG=false
- [ ] APP_KEY が設定されている
- [ ] SESSION_SECURE_COOKIE=true
- [ ] HTTPS が有効
- [ ] デフォルトパスワードを変更
- [ ] 不要なポートを閉じる
- [ ] ファイアウォール設定

### 7.2 推奨セキュリティヘッダー

Nginx設定:

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```
