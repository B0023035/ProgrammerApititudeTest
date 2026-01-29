# Laravel + Vue + Docker環境での419エラー（CSRF Token Mismatch）原因一覧

## 目次
1. [セッション関連の問題](#セッション関連の問題)
2. [CSRF トークン関連の問題](#csrfトークン関連の問題)
3. [Cookie関連の問題](#cookie関連の問題)
4. [Docker環境固有の問題](#docker環境固有の問題)
5. [ネットワーク・プロキシの問題](#ネットワークプロキシの問題)
6. [設定・キャッシュの問題](#設定キャッシュの問題)
7. [ミドルウェア・ルーティングの問題](#ミドルウェアルーティングの問題)
8. [Vue/フロントエンドの問題](#vueフロントエンドの問題)
9. [パーミッション・ストレージの問題](#パーミッションストレージの問題)
10. [その他の問題](#その他の問題)

---

## セッション関連の問題

### 1. セッションドメインの不一致
Docker環境では、`SESSION_DOMAIN`の設定が重要です。

**解決方法：**
```env
# .env
SESSION_DOMAIN=localhost
# または
SESSION_DOMAIN=.yourdomain.com
```

### 2. セッションドライバーの問題
Dockerでは`file`ドライバーよりも`redis`や`database`が推奨されます。

**解決方法：**
```env
SESSION_DRIVER=redis
# または
SESSION_DRIVER=database
```

### 3. セッションライフタイムの短さ
セッションが早期に期限切れになっている。

**解決方法：**
```env
SESSION_LIFETIME=120  # 分単位で増やす
```

### 4. セッションCookieの名前衝突
複数のLaravelアプリケーションが同じホストで動いている。

**解決方法：**
```env
SESSION_COOKIE=myapp_session  # ユニークな名前に変更
```

### 5. セッションテーブルの不備
データベースドライバー使用時にテーブルが存在しない。

**解決方法：**
```bash
php artisan session:table
php artisan migrate
```

---

## CSRFトークン関連の問題

### 6. CSRFトークンの取得・送信の問題
Vueコンポーネントで適切にCSRFトークンを送信していない。

**解決方法：**
```javascript
// axiosの設定例
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// またはaxiosのデフォルト設定
axios.defaults.withCredentials = true;
```

### 7. SPAの初回アクセス時のトークン取得漏れ
SPA（Single Page Application）でCSRFトークンを事前に取得していない。

**解決方法：**
```javascript
// アプリケーション起動時に実行
await axios.get('/sanctum/csrf-cookie');
// その後にPOSTリクエスト
await axios.post('/api/data', data);
```

### 8. X-XSRF-TOKENヘッダーの問題
LaravelはデフォルトでX-XSRF-TOKENヘッダーも確認しますが、送信されていない。

**解決方法：**
```javascript
// AxiosはXSRF-TOKENクッキーを自動的にX-XSRF-TOKENヘッダーに変換
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
```

---

## Cookie関連の問題

### 9. SameSite Cookie設定
クロスサイトリクエストでCookieが送信されない。

**解決方法：**
```env
SESSION_SECURE_COOKIE=false  # 開発環境でHTTPSを使わない場合
SESSION_SAME_SITE=lax
```

### 10. Cookieのパス設定
セッションCookieのパスが適切でない。

**解決方法：**
```env
SESSION_PATH=/
```

### 11. ブラウザのCookie制限
サードパーティCookieがブロックされている（特にSafariやFirefox）。

**解決方法：**
```env
SESSION_SECURE_COOKIE=false  # 開発環境
SESSION_SAME_SITE=none  # クロスサイトの場合（本番ではlax推奨）
```

### 12. HTTPとHTTPSの混在
フロントエンドがHTTPSでバックエンドがHTTPの場合。

**解決方法：**
```env
SESSION_SECURE_COOKIE=false  # または両方HTTPSに統一
```

---

## Docker環境固有の問題

### 13. Docker volumeのマウント問題
`storage`ディレクトリがホストとコンテナで同期されていない。

**解決方法：**
```yaml
# docker-compose.yml
volumes:
  - ./storage:/var/www/html/storage  # 明示的にマウント
```

### 14. 複数のLaravelインスタンス
Docker Composeで複数のアプリケーションコンテナを起動している場合。

**解決方法：**
```yaml
# docker-compose.ymlで1つのインスタンスに制限
deploy:
  replicas: 1
```

### 15. Docker network設定の問題
コンテナ間通信でIPアドレスが変動している。

**解決方法：**
```yaml
# docker-compose.yml
networks:
  app-network:
    driver: bridge
```

### 16. Docker Composeのビルドキャッシュ
古いイメージがキャッシュされている。

**解決方法：**
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### 17. Dockerのホスト名解決問題
コンテナ内でlocalhostが正しく解決されない。

**解決方法：**
```yaml
# docker-compose.yml
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### 18. Docker Composeのサービス起動順序
データベースやRedisが起動する前にアプリケーションが起動している。

**解決方法：**
```yaml
# docker-compose.yml
depends_on:
  - db
  - redis
```

### 19. タイムゾーンの不一致
Dockerコンテナとホストマシンのタイムゾーンがずれている。

**解決方法：**
```env
APP_TIMEZONE=Asia/Tokyo
```
```dockerfile
# Dockerfileで
ENV TZ=Asia/Tokyo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
```

### 20. .envファイルの読み込み失敗
Dockerコンテナ内で.envファイルがマウントされていない。

**解決方法：**
```yaml
# docker-compose.yml
volumes:
  - ./.env:/var/www/html/.env
```

### 21. メモリ不足
Dockerコンテナのメモリ制限でセッション処理が失敗。

**解決方法：**
```yaml
# docker-compose.yml
deploy:
  resources:
    limits:
      memory: 512M
```

---

## ネットワーク・プロキシの問題

### 22. TrustProxiesミドルウェアの設定
Docker環境ではプロキシ設定が必要な場合がある。

**解決方法：**
```php
// app/Http/Middleware/TrustProxies.php
protected $proxies = '*';
```

### 23. nginx/Apacheのリバースプロキシ設定
プロキシ経由でヘッダーが正しく転送されていない。

**解決方法：**
```nginx
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header Host $host;
```

### 24. CORSの設定問題
フロントエンドとバックエンドが異なるポートで動いている場合。

**解決方法：**
```php
// config/cors.php
'supports_credentials' => true,
'paths' => ['api/*', 'sanctum/csrf-cookie'],
```

### 25. ロードバランサー配下での問題
複数のコンテナにリクエストが分散され、セッションが共有されていない。

**解決方法：**
```yaml
# Stickyセッションの設定が必要
# または共有セッションストレージ（Redis/Database）を使用
```

### 26. ファイアウォール/セキュリティソフト
Dockerコンテナへの通信がブロックされている。

**解決方法：**
- ファイアウォール設定の確認
- セキュリティソフトの例外設定

---

## 設定・キャッシュの問題

### 27. APP_KEYの未設定・不一致
暗号化に使用されるキーが正しく設定されていない。

**解決方法：**
```bash
php artisan key:generate
# Dockerコンテナを再起動
```

### 28. キャッシュの問題
古い設定がキャッシュされている。

**解決方法：**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 29. Laravelのバージョン不一致
composer.lockとvendorディレクトリが同期していない。

**解決方法：**
```bash
composer install
# またはコンテナ内で
docker-compose exec app composer install
```

### 30. php.iniのセッション設定
PHPのセッション設定が不適切。

**解決方法：**
```ini
session.cookie_httponly = On
session.cookie_samesite = "Lax"
session.gc_maxlifetime = 7200
```

### 31. 古いブラウザキャッシュ
開発中にブラウザキャッシュが残っている。

**解決方法：**
- ハードリフレッシュ（Ctrl+Shift+R）
- シークレットモードでテスト

---

## ミドルウェア・ルーティングの問題

### 32. ミドルウェアグループの問題
APIルートで`web`ミドルウェアグループが適用されていない、または逆に重複している。

**解決方法：**
```php
// routes/web.php - CSRFチェックあり
Route::post('/api/data', [Controller::class, 'store']);

// routes/api.php - CSRFチェックなし（通常はトークン認証）
Route::post('/data', [Controller::class, 'store']);
```

### 33. VerifyCsrfTokenミドルウェアの優先順位
ミドルウェアスタックの順序が不適切。

**解決方法：**
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        // StartSessionの後にVerifyCsrfTokenが必要
        \App\Http\Middleware\VerifyCsrfToken::class,
    ],
];
```

### 34. PreflightリクエストのCSRFチェック
OPTIONSリクエストでCSRFトークンがチェックされている。

**解決方法：**
```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    // OPTIONSは自動的に除外されるべきだが、念のため確認
];
```

### 35. LaravelのAPI Rate Limiting
レート制限に引っかかっている。

**解決方法：**
```php
// app/Http/Kernel.php
'throttle:api' => \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
```

---

## Vue/フロントエンドの問題

### 36. AjaxリクエストでのCredentials設定漏れ
VueのaxiosでCredentialsが送信されていない。

**解決方法：**
```javascript
// すべてのリクエストに適用
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// または個別のリクエストで
axios.post('/api/data', data, {
  withCredentials: true
});
```

### 37. フォームの二重送信
JavaScriptで複数回リクエストが送信されている。

**解決方法：**
```javascript
// ボタンの無効化やフラグで制御
let isSubmitting = false;
if (isSubmitting) return;
isSubmitting = true;
```

### 38. Laravel Mixのホットリロード問題
開発環境でHMR（Hot Module Replacement）使用時の問題。

**解決方法：**
```javascript
// webpack.mix.js
mix.webpackConfig({
    devServer: {
        headers: { 'Access-Control-Allow-Origin': '*' }
    }
});
```

---

## パーミッション・ストレージの問題

### 39. セッションストレージのパーミッション問題
Dockerコンテナ内で`storage/framework/sessions`ディレクトリの書き込み権限がない。

**解決方法：**
```bash
chmod -R 775 storage
chown -R www-data:www-data storage
```

### 40. Redisの接続問題
セッションドライバーがRedisの場合、接続エラー。

**解決方法：**
```env
REDIS_HOST=redis  # Dockerサービス名に合わせる
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## その他の問題

### 41. Sanctum使用時の設定
API認証にSanctumを使っている場合。

**解決方法：**
```env
SANCTUM_STATEFUL_DOMAINS=localhost:8080,127.0.0.1:8080
```

### 42. Laravel Sanctumの初期化漏れ
Sanctumを使用している場合の設定不足。

**解決方法：**
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 43. Laravel Debugbarなどのパッケージ干渉
開発用パッケージがセッションに干渉。

**解決方法：**
```bash
# 一時的に無効化してテスト
composer remove barryvdh/laravel-debugbar --dev
```

### 44. Laravelのメンテナンスモード
アプリケーションがメンテナンスモードになっている。

**解決方法：**
```bash
php artisan up
```

### 45. CSPヘッダーの制限
Content Security Policyが厳しすぎる。

**解決方法：**
- CSPヘッダーの設定を確認・緩和

---

## デバッグ方法

### ログの確認
```bash
tail -f storage/logs/laravel.log
```

### セッション情報の確認
```php
dd(session()->all());
```

### リクエストヘッダーの確認
```php
Log::info(request()->headers->all());
```

### ブラウザ開発者ツール
- NetworkタブでCookieとヘッダーを確認
- ConsoleタブでJavaScriptエラーを確認

### 一時的なCSRFチェック無効化（デバッグ用）
```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    '*'  // すべてのルートを除外（本番環境では絶対に使用しない）
];
```

---

## チェックリスト

問題を特定するために、以下の順序で確認することをお勧めします：

- [ ] ブラウザの開発者ツールでCookieが正しく設定されているか確認
- [ ] `storage/logs/laravel.log`でエラーログを確認
- [ ] `.env`ファイルの設定を確認
- [ ] Dockerコンテナが正しく起動しているか確認
- [ ] `php artisan config:clear`でキャッシュをクリア
- [ ] シークレットモードまたは別のブラウザで試す
- [ ] セッションストレージのパーミッションを確認
- [ ] axiosの設定を確認
- [ ] Sanctum使用時は`/sanctum/csrf-cookie`を事前に呼び出しているか確認
- [ ] CORSとSameSite Cookieの設定を確認

---

**最終更新:** 2025年12月

このドキュメントは、Laravel + Vue + Docker環境での419エラーの主な原因と解決方法をまとめたものです。
問題が解決しない場合は、複数の原因が組み合わさっている可能性があるため、一つずつ確認していくことをお勧めします。