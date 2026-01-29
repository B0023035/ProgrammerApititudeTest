# プログラマー適性検査システム - プロジェクト構成

## 📋 プロジェクト概要

**名称**: プログラマー適性検査システム (Programmer Aptitude Test)  
**フレームワーク**: Laravel 11 + Vue 3 (Inertia.js)  
**データベース**: MySQL  
**デプロイ**: Docker (Sail)

本システムは、プログラマー職の適性を評価するためのオンライン試験プラットフォームです。
一般ユーザー（認証ユーザー）とゲストの両方に対応した試験実施機能を提供します。

---

## 🏗️ システムアーキテクチャ

### フロントエンド

- **フレームワーク**: Vue 3 (TypeScript)
- **SPA フレームワーク**: Inertia.js v2
- **ルーティング**: Inertia 組み込みルーティング
- **スタイル**: Tailwind CSS
- **ビルドツール**: Vite

### バックエンド

- **フレームワーク**: Laravel 11
- **言語**: PHP 8.4
- **キャッシング**: Redis / Database Cache
- **セッション**: Database

### データベース

- **DBMS**: MySQL 8.0
- **ORM**: Eloquent (Laravel)

---

## 📁 ディレクトリ構成

```
ProgrammerAptitudeTest/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ExamController.php           # 本番試験ロジック
│   │   │   ├── PracticeController.php       # 練習問題ロジック
│   │   │   ├── AuthController.php           # 認証ロジック
│   │   │   ├── SessionCodeController.php    # セッションコード管理
│   │   │   ├── Admin/
│   │   │   │   ├── AdminAuthController.php  # 管理者認証
│   │   │   │   ├── EventManagementController.php  # イベント管理
│   │   │   │   └── ResultsManagementController.php # 成績管理
│   │   │   └── ...
│   │   ├── Middleware/
│   │   │   ├── HandleInertiaRequests.php    # Inertia props 共有
│   │   │   ├── CheckSessionCode.php         # セッションコード検証
│   │   │   ├── AdminMiddleware.php          # 管理者認証チェック
│   │   │   └── ...
│   │   └── Requests/
│   ├── Models/                              # Eloquent モデル
│   │   ├── User.php                         # 一般ユーザー
│   │   ├── Admin.php                        # 管理者ユーザー
│   │   ├── ExamSession.php                  # 試験セッション
│   │   ├── Answer.php                       # 試験解答
│   │   ├── Question.php                     # 本番試験問題
│   │   ├── Choice.php                       # 本番問題選択肢
│   │   ├── PracticeQuestion.php             # 練習問題
│   │   ├── PracticeChoice.php               # 練習問題選択肢
│   │   ├── Event.php                        # 試験イベント
│   │   ├── ExamViolation.php                # 不正検知記録
│   │   └── ...
│   └── Providers/
├── bootstrap/
│   ├── app.php                              # アプリケーション設定
│   └── providers.php                        # プロバイダー登録
├── config/
│   ├── app.php                              # アプリケーション設定
│   ├── auth.php                             # 認証設定（web/admin ガード）
│   ├── session.php                          # セッション設定
│   ├── cache.php                            # キャッシング設定
│   ├── database.php                         # データベース設定
│   └── ...
├── database/
│   ├── migrations/                          # マイグレーションファイル
│   ├── seeders/                             # シーダーファイル
│   └── factories/                           # モデルファクトリー
├── resources/
│   ├── js/
│   │   ├── app.ts                           # Vue アプリケーション初期化
│   │   ├── bootstrap.ts                     # Axios 設定・CSRF トークン管理
│   │   ├── Pages/                           # Inertia ページコンポーネント
│   │   │   ├── Welcome.vue                  # ウェルカムページ
│   │   │   ├── TestStart.vue                # テスト開始画面
│   │   │   ├── Part.vue                     # 本番試験（パート表示）
│   │   │   ├── PracticeExplanation.vue      # 練習問題解説
│   │   │   ├── Result.vue                   # 試験結果表示
│   │   │   ├── Auth/
│   │   │   │   ├── Login.vue                # 一般ユーザーログイン
│   │   │   │   └── Register.vue             # 一般ユーザー登録
│   │   │   ├── Admin/
│   │   │   │   ├── Login.vue                # 管理者ログイン
│   │   │   │   ├── Dashboard.vue            # 管理者ダッシュボード
│   │   │   │   └── ...
│   │   │   └── ...
│   │   ├── Layouts/                         # レイアウトコンポーネント
│   │   │   ├── AuthenticatedLayout.vue      # 認証済みユーザー向けレイアウト
│   │   │   ├── GuestLayout.vue              # ゲスト向けレイアウト
│   │   │   └── AdminLayout.vue              # 管理者向けレイアウト
│   │   ├── Components/                      # 再利用可能なコンポーネント
│   │   │   ├── Navbar.vue
│   │   │   ├── Timer.vue
│   │   │   └── ...
│   │   └── types/                           # TypeScript 型定義
│   ├── views/
│   │   └── app.blade.php                    # HTML エントリーポイント
│   └── css/
│       └── app.css                          # グローバルスタイル
├── routes/
│   ├── web.php                              # Web ルート定義
│   ├── auth.php                             # 認証ルート
│   └── api.php                              # API ルート（未使用）
├── storage/                                 # ファイルストレージ
├── tests/                                   # テストファイル
├── vendor/                                  # Composer 依存関係
├── .env                                     # 環境変数
├── docker-compose.yml                       # Docker Compose 設定
├── Dockerfile                               # Docker イメージ定義
├── vite.config.js                           # Vite 設定
├── tailwind.config.js                       # Tailwind CSS 設定
└── tsconfig.json                            # TypeScript 設定
```

---

## 📊 データベーススキーマ

### テーブル関係図

```
users (一般ユーザー)
├── exam_sessions (1ユーザー:多セッション)
│   ├── answers (1セッション:多解答)
│   └── exam_violations (1セッション:多違反記録)
├── questions (1ユーザー → 多問題)
└── (他多数)

admins (管理者ユーザー)
└── (認証専用)

events (試験イベント)
├── exam_sessions (1イベント:多セッション)
├── questions (問題の出題設定)
└── (イベント設定)

questions (本番問題)
├── choices (1問題:多選択肢)
└── answers (多解答)

practice_questions (練習問題)
├── practice_choices (1問題:多選択肢)
└── (練習用)
```

### 主要テーブル詳細

#### `users` テーブル

```
id (PK)
name (ユーザー名)
email (メールアドレス)
password (ハッシュ化パスワード)
admission_year (入学年度)
email_verified_at (メール確認済み日時)
created_at
updated_at
```

#### `admins` テーブル

```
id (PK)
name (管理者名)
email (メールアドレス)
password (ハッシュ化パスワード)
remember_token
created_at
updated_at
```

#### `exam_sessions` テーブル

```
id (PK)
user_id (FK -> users)
event_id (FK -> events)
session_uuid (セッション識別子)
grade (受験時の学年)
started_at (開始時刻)
finished_at (終了時刻)
disqualified_at (失格時刻)
disqualification_reason (失格理由)
current_part (現在のパート:1-3)
current_question (現在の問題番号)
remaining_time (残り時間(秒))
security_log (JSON: セキュリティログ)
created_at
updated_at
```

#### `answers` テーブル

```
id (PK)
user_id (FK -> users)
exam_session_id (FK -> exam_sessions)
question_id (FK -> questions)
part (パート:1-3)
choice (選択された選択肢)
is_correct (正解フラグ)
created_at
updated_at
```

#### `questions` テーブル

```
id (PK)
part (パート:1-3)
number (問題番号)
text (問題文)
image (画像ファイル名)
created_at
updated_at
```

#### `choices` テーブル

```
id (PK)
question_id (FK -> questions)
part (パート)
label (選択肢ラベル: A,B,C,D,E)
text (選択肢テキスト)
image (選択肢画像)
is_correct (正解判定)
created_at
updated_at
```

#### `events` テーブル

```
id (PK)
name (イベント名)
passphrase (セッションコード/パスフレーズ)
begin (試験開始日時)
end (試験終了日時)
exam_type (試験タイプ: full/part1/part2/part3)
part1_questions (パート1問題数)
part1_time (パート1時間制限(秒))
part2_questions (パート2問題数)
part2_time (パート2時間制限(秒))
part3_questions (パート3問題数)
part3_time (パート3時間制限(秒))
created_at
updated_at
```

#### `practice_questions` テーブル

```
id (PK)
section (セクション:1-3)
question (問題文)
options (JSON: 選択肢)
answer (正答)
explanation (解説)
created_at
updated_at
```

#### `exam_violations` テーブル

```
id (PK)
exam_session_id (FK -> exam_sessions)
violation_type (違反タイプ)
details (JSON: 詳細)
created_at
```

---

## 🔐 認証システム

### ガード (Guard) 設定

```php
// config/auth.php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',      // 一般ユーザー認証
    ],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',     // 管理者認証
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => User::class,
    ],
    'admins' => [
        'driver' => 'eloquent',
        'model' => Admin::class,
    ],
],
```

### 認証フロー

#### 一般ユーザー

1. `/` - セッションコード入力
2. `/login` - ユーザーログイン
3. `/test-start` - テスト開始準備
4. `/practice/{section}` - 練習問題
5. `/practice/explanation/{part}` - 練習問題解説
6. `/exam/start` - 本番試験開始 (POST)
7. `/exam/part/{part}` - 本番試験実施
8. `/exam/result/{uuid}` - 結果表示

#### ゲストユーザー

1. `/` - セッションコード入力
2. `/guest/info` - ゲスト情報入力
3. `/guest/practice/{section}` - ゲスト練習問題
4. `/guest/exam/start` - ゲスト本番試験開始 (POST)
5. `/guest/exam/part/{part}` - ゲスト本番試験実施
6. `/guest/result` - ゲスト結果表示

#### 管理者

1. `/admin/login` - 管理者ログイン
2. `/admin/dashboard` - ダッシュボード
3. `/admin/events` - イベント管理
4. `/admin/results` - 成績管理
5. `/admin/questions` - 問題管理
6. `/admin/users` - ユーザー管理

---

## 🎯 主要機能

### 1. セッションコード管理

- セッションコード入力・検証
- イベント情報の取得と展開
- セッション情報の保存

**ファイル**: `SessionCodeController.php`

### 2. 練習問題システム

- パート別練習問題表示
- 解答入力・保存
- 解説表示
- 本番試験への遷移

**ファイル**: `PracticeController.php`, `PracticeExplanation.vue`

### 3. 本番試験システム

- パート別試験実施
- リアルタイムタイマー
- 解答の保存・自動保存
- セキュリティ監視
- 不正検知

**ファイル**: `ExamController.php`, `Part.vue`

### 4. 成績管理

- 試験結果の集計
- 学年別統計
- 成績レポート表示

**ファイル**: `ResultsManagementController.php`

### 5. 管理画面

- イベント作成・編集
- 問題管理
- ユーザー管理
- 成績確認

**ファイル**: `Admin/*Controller.php`

---

## 🔄 リクエスト/レスポンスフロー

### 典型的なリクエストフロー

```
Client (Vue 3)
    ↓ (Inertia リクエスト)
Middleware Stack
    ├── VerifyCsrfToken         (CSRF 検証)
    ├── StartSession            (セッション開始)
    ├── SubstituteBindings      (モデルバインド)
    ├── CheckSessionCode        (セッションコード検証)
    ├── Authenticate            (認証チェック)
    └── HandleInertiaRequests   (Props 準備)
    ↓
Controller
    ├── ビジネスロジック実行
    ├── データベースクエリ
    └── Inertia::render()
    ↓
Inertia Response
    ├── JSON レスポンス返送
    ├── CSRF トークン更新
    └── Vue コンポーネント
    ↓
Client (Vue 3)
    └── コンポーネント再描画
```

---

## 🔌 CSRF トークン管理

### トークンフロー

```
1. ページロード時
   └── app.blade.php
       └── <meta name="csrf-token" content="{{ csrf_token() }}">

2. JavaScript 初期化
   └── app.ts
       └── Inertia props に csrf トークン追加

3. bootstrap.ts
   └── Axios デフォルトヘッダーに CSRF トークン設定

4. リクエスト送信 (useForm.post())
   └── X-CSRF-TOKEN ヘッダーに自動追加

5. レスポンス受信
   └── props.csrf で新しいトークン取得
   └── メタタグ・ヘッダー更新
```

### 実装詳細

```typescript
// resources/js/bootstrap.ts
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

// リクエスト前インターセプター
axios.interceptors.request.use(config => {
    const currentToken = getCsrfToken();
    if (currentToken) {
        config.headers["X-CSRF-TOKEN"] = currentToken;
    }
    return config;
});

// レスポンス後インターセプター
axios.interceptors.response.use(response => {
    const newToken = response.headers["x-csrf-token"];
    if (newToken) {
        updateMetaTag(newToken);
        updateAxiosDefault(newToken);
    }
    return response;
});
```

---

## 🛡️ セキュリティ機能

### 1. CSRF 保護

- トークンベースの検証
- ページ遷移ごとのトークン更新
- Axios インターセプターによる自動管理

### 2. セッション管理

- データベースベースのセッション保存
- セッション ID の安全な管理
- 失格ユーザーのセッション無効化

### 3. 不正検知

- タブ切り替え検出
- ウィンドウフォーカス離脱検出
- キーボードショートカット無効化
- 画面キャプチャ検出

**実装**: `Part.vue` の SecurityMonitor

### 4. 認証・認可

- ガード (guard) ベースの認証分離
- ミドルウェアによるルート保護
- ロールベースのアクセス制御

---

## 📦 キャッシング戦略

### キャッシュの種類

#### 1. 問題データキャッシュ

```php
Cache::remember("questions_part_{$part}", 60*60*24, function() {
    return Question::where('part', $part)->get();
});
```

#### 2. ユーザーセッションキャッシュ

```php
Cache::put(
    "exam_session_{$userId}_{$part}",
    $sessionData,
    2 * 60 * 60  // 2時間
);
```

#### 3. ゲストセッションキャッシュ

```php
Cache::put(
    "guest_exam_session_{$guestId}",
    $guestSession,
    2 * 60 * 60  // 2時間
);
```

---

## 🚀 デプロイメント

### Docker 構成

```yaml
# docker-compose.yml
services:
    laravel.test:
        image: sail-8.4/app # PHP 8.4 + Laravel Sail
        environment:
            - DB_CONNECTION=mysql
            - DB_HOST=mysql
            - REDIS_HOST=redis
        ports:
            - "80:80"

    mysql:
        image: mysql:8.0
        environment:
            - MYSQL_DATABASE=laravel
            - MYSQL_ROOT_PASSWORD=password

    redis:
        image: redis:7-alpine
        ports:
            - "6379:6379"
```

### マイグレーション

```bash
# マイグレーション実行
docker compose exec -T laravel.test php artisan migrate --force

# シード実行
docker compose exec -T laravel.test php artisan db:seed
```

---

## 📈 パフォーマンス考慮事項

### 1. データベース最適化

- インデックス設定
- N+1 クエリの回避
- キャッシュの活用

### 2. フロントエンド最適化

- Vue 3 の Composition API
- 遅延ロード
- イメージ最適化

### 3. セッション管理

- キャッシュの有効活用
- セッション有効期限管理
- 不要なキャッシュ削除

---

## 🔧 環境変数

```env
# アプリケーション設定
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=true
APP_URL=http://localhost

# データベース
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# セッション
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SAME_SITE=lax

# キャッシュ
CACHE_STORE=database

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# その他
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

---

## 📝 開発ガイドライン

### コーディング規約

- PSR-12 (PHP Standard Recommendation)
- Vue 3 Composition API を使用
- TypeScript での型定義
- Blade テンプレート の使用最小化

### ログ記録

- 重要な処理の開始・終了をログ
- エラー・警告の詳細記録
- ユーザーアクション追跡

### テスト

```bash
# ユニットテスト
./vendor/bin/phpunit

# 機能テスト
php artisan test
```

---

## 🐛 トラブルシューティング

### 一般的な問題と解決策

#### 1. CSRF トークンエラー (419)

**症状**: ログイン・フォーム送信時に 419 エラー  
**原因**: CSRF トークン有効期限切れまたは不一致  
**解決**:

- メタタグのトークンを確認
- bootstrap.ts での設定確認
- キャッシュのクリア

#### 2. セッションが保存されない

**症状**: ログイン後もセッション情報が失われる  
**原因**: セッション保存先の権限問題  
**解決**:

```bash
chown -R sail:sail storage/ bootstrap/cache/
chmod -R 777 storage/ bootstrap/cache/
```

#### 3. パーミッションエラー

**症状**: ファイル書き込み失敗  
**原因**: ストレージディレクトリの所有権問題  
**解決**:

```bash
docker compose exec -T laravel.test bash -c \
  "chown -R sail:sail /var/www/html/storage"
```

---

## 📚 参考資料

- [Laravel 11 公式ドキュメント](https://laravel.com/docs)
- [Inertia.js 公式ドキュメント](https://inertiajs.com/)
- [Vue 3 公式ドキュメント](https://vuejs.org/)
- [Tailwind CSS 公式ドキュメント](https://tailwindcss.com/)

---

**最終更新**: 2025年12月9日  
**バージョン**: 1.0
