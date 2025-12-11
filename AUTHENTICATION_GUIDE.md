# 認証フロー・開発ガイド

## 🔐 認証システム詳細

### システム概要

本システムは3つの認証パターンに対応しています:

1. **一般ユーザー認証** (Guard: `web`)
2. **ゲストユーザー認証** (セッションベース)
3. **管理者認証** (Guard: `admin`)

---

## 👥 一般ユーザー認証フロー

### 全体フロー図

```
┌─────────────────────────────────────────────────────────────────┐
│                        初期状態 (未認証)                          │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────┐
        │  セッションコード入力   │ → Verify
        │   /session-code/verify │
        └────────────┬───────────┘
                     │
                     ▼
        ┌────────────────────────┐
        │  ユーザーログイン       │ → POST /login
        │   /login               │    auth:web
        └────────────┬───────────┘
                     │
         ┌───────────┴───────────┐
         │ (ログイン失敗)         │ (ログイン成功)
         ▼                       ▼
      401 Error            セッション作成
                           User 設定
                                │
                                ▼
                    ┌─────────────────────┐
                    │ ユーザー認証済み    │
                    │ (Guard: web)        │
                    └─────────┬───────────┘
                              │
                    ┌─────────┴──────────┐
                    │                    │
                    ▼                    ▼
            ┌──────────────┐    ┌────────────────┐
            │ 練習問題     │    │ 本番試験        │
            │ /practice/{s}│    │ /exam/part/{p} │
            └──────────────┘    └────────────────┘
                    │                    │
                    └─────────┬──────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │ 成績確認          │
                    │ /exam/result/{uuid}
                    └─────────┬────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │ ログアウト        │
                    │ POST /logout      │
                    └──────────────────┘
```

---

### 認証状態遷移表

| 状態       | ガード | User 設定      | セッション | アクセス可能                    |
| ---------- | ------ | -------------- | ---------- | ------------------------------- |
| 未認証     | 無     | null           | リセット   | /, /login, /register, /guest/\* |
| 認証済み   | web    | User instance  | 有効       | /practice/_, /exam/_, 全ページ  |
| ゲスト     | 無     | null           | キャッシュ | /guest/exam/\*, /guest/result   |
| 管理者認証 | admin  | Admin instance | 有効       | /admin/\*                       |

---

## 🔑 ログインプロセス詳細

### ステップ1: ログインフォーム表示

```
GET /login

Response: Inertia::render('Auth/Login', [
    'csrf_token' => csrf_token(),
])
```

**Vue コンポーネント**: `resources/js/Pages/Auth/Login.vue`

```vue
<template>
    <form @submit.prevent="submit">
        <input v-model="form.email" type="email" placeholder="メールアドレス" required />
        <input v-model="form.password" type="password" placeholder="パスワード" required />
        <label>
            <input v-model="form.remember" type="checkbox" />
            ログイン状態を保持する
        </label>
        <button type="submit" :disabled="form.processing">ログイン</button>
    </form>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>
```

---

### ステップ2: 認証処理

```
POST /login

Request Body:
{
    "email": "user@example.com",
    "password": "password123",
    "remember": false
}

Middleware Stack:
1. VerifyCsrfToken ✓
2. StartSession ✓
3. Authenticate ✓ (ゲストのみ許可)
4. HandleInertiaRequests ✓
```

**コントローラー処理** (`AuthController@login()`):

```php
public function login(Request $request)
{
    // 入力バリデーション
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // 認証試行
    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        // ✓ 認証成功
        $request->session()->regenerate();

        return redirect()->intended(route('test-start'));
    }

    // ✗ 認証失敗
    return back()->withErrors([
        'email' => 'メールアドレスまたはパスワードが正しくありません',
    ]);
}
```

**認証フロー**:

```
1. credentials から Email を検索
2. User モデルで検索
3. Password ハッシュ比較 (bcrypt)
4. 一致 → Session に user_id 保存 → Auth::check() = true
5. 不一致 → エラーメッセージ返送
```

---

### ステップ3: セッション作成

```php
// Laravel がセッションを自動作成
// 以下の情報が SESSIONS テーブルに保存される

Session {
    id: "session_uuid_string",
    user_id: 1,                              // 認証ユーザーID
    ip_address: "192.168.1.100",
    user_agent: "Mozilla/5.0...",
    payload: "serialized_session_data",
    last_activity: 1672531200,
}
```

**セッション検証**:

```php
// リクエストメソッド内で
Auth::check();        // true/false
Auth::user();         // User instance
Auth::id();          // User ID
Auth::user()->name;  // "田中太郎"
```

---

## 🛡️ CSRF トークン保護メカニズム

### トークン生成・検証フロー

```
┌──────────────────────────────────────────┐
│ 1. ページロード (GET /login)             │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 2. Laravel がトークン生成                │
│    - Session に保存: _token              │
│    - Meta タグに埋め込み                 │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 3. HTML レスポンス                        │
│    <meta name="csrf-token"               │
│          content="abc123xyz">            │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 4. JavaScript で読み込み                 │
│    - Meta タグから取得                   │
│    - Axios デフォルトヘッダーに設定      │
│    - useForm に自動含有                  │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 5. フォーム送信 (POST /login)            │
│    Headers: {                            │
│        X-CSRF-TOKEN: "abc123xyz"        │
│    }                                     │
│    Body: { email, password }            │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 6. Laravel VerifyCsrfToken ミドルウェア  │
│    - リクエストの CSRF トークン取得      │
│    - Session の _token と比較           │
│    - 一致 → リクエスト通す               │
│    - 不一致 → 419 エラー                 │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 7. レスポンス返送                        │
│    - 新しいトークン生成                  │
│    - X-CSRF-TOKEN ヘッダーに含める       │
│    - Inertia props に含める              │
└───────────┬────────────────────────────┘
            │
            ▼
┌──────────────────────────────────────────┐
│ 8. ブラウザ側で更新                      │
│    - Meta タグ更新                       │
│    - Axios ヘッダー更新                  │
│    - props.csrf_token 更新               │
└──────────────────────────────────────────┘
```

---

### トークン取得箇所

#### フロントエンド (resources/js/bootstrap.ts)

```typescript
// Meta タグから取得
function getCsrfToken(): string | null {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    return token || null;
}

// Axios デフォルト設定
const token = getCsrfToken();
if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

// リクエスト前に確認
axios.interceptors.request.use(config => {
    const currentToken = getCsrfToken();
    if (currentToken) {
        config.headers["X-CSRF-TOKEN"] = currentToken;
    }
    return config;
});

// レスポンスで新トークン受け取り
axios.interceptors.response.use(response => {
    const newToken = response.headers["x-csrf-token"];
    if (newToken) {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute("content", newToken);
        }
    }
    return response;
});
```

---

## 👤 ゲストユーザーフロー

### ゲスト認証の特徴

- **認証なし**: ユーザーのメールアドレス・パスワード不要
- **キャッシュベース**: セッション情報は Redis/Database Cache に保存
- **一時的**: 試験終了後、キャッシュ有効期限切れで削除

### ゲストアクセスフロー

```
1. セッションコード入力 → /session-code/verify
2. ゲスト情報入力 → /guest/info
3. 情報送信 → POST /guest/info/submit
   └─ キャッシュに保存: cache('guest_{timestamp}', $guestData)
4. 試験開始 → POST /guest/exam/start
   └─ セッションコード検証
   └─ キャッシュからゲスト情報取得
5. パート表示 → GET /guest/exam/part/{part}
6. 解答送信 → POST /guest/exam/part/{part}/answer
7. 結果表示 → GET /guest/result

# キャッシュ有効期限: 2時間 (120分)
```

---

### ゲスト情報キャッシュ構造

```php
// Cache Key: "guest_exam_{guest_id}"
$guestData = [
    'guest_id' => 'uuid',
    'name' => '山田花子',
    'grade' => '高3',
    'school' => '○○高等学校',
    'event_id' => 1,
    'session_code' => 'EXAM2025',
    'started_at' => '2025-01-15 10:00:00',
];

Cache::put("guest_exam_{guest_id}", $guestData, 2 * 60 * 60);
```

---

## 🔑 管理者認証フロー

### 管理者ログイン

```
POST /admin/login

Guard: admin
Provider: admins (Admin モデル)
```

**コントローラー**: `Admin\AdminAuthController@login()`

```php
public function login(Request $request)
{
    // Admin モデルで認証
    if (Auth::guard('admin')->attempt([
        'email' => $request->email,
        'password' => $request->password,
    ])) {
        $request->session()->regenerate();
        return redirect('/admin/dashboard');
    }

    return back()->withErrors(['email' => '認証失敗']);
}
```

**ミドルウェア検証**:

```php
// Middleware: AdminMiddleware
if (!Auth::guard('admin')->check()) {
    return redirect('/admin/login');
}
```

---

## 🔄 認証状態の確認・管理

### Vue コンポーネント内での認証確認

```vue
<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => page.props.isAdmin);
const csrfToken = computed(() => page.props.csrf_token);

// 認証確認
if (user.value) {
    console.log("ユーザー:", user.value.name);
}

// 管理者確認
if (isAdmin.value) {
    console.log("管理者ユーザー");
}

// CSRF トークン
console.log("CSRF Token:", csrfToken.value);
</script>
```

---

### Blade テンプレートでの認証確認

```blade
@auth
    <p>ログイン済み: {{ Auth::user()->name }}</p>
@else
    <p>未ログイン</p>
@endauth

@guest('admin')
    <p>管理者ではありません</p>
@endguest

@auth('admin')
    <p>管理者ユーザー: {{ Auth::guard('admin')->user()->name }}</p>
@endauth
```

---

## 🚪 ログアウト処理

### ログアウト実装

```php
// AuthController.php
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
}
```

### ログアウトのステップ

```
1. Auth::logout()
   └─ セッションから user_id 削除
   └─ Auth::check() = false

2. $request->session()->invalidate()
   └─ SESSIONS テーブルからレコード削除
   └─ ブラウザの Session Cookie 削除

3. $request->session()->regenerateToken()
   └─ CSRF トークンを再生成

4. redirect('/')
   └─ ホームページへリダイレクト
```

---

## 🛠️ 開発ガイドライン

### 1. 新しいルートの作成

#### 認証が必要なルート

```php
// routes/web.php
Route::middleware(['auth:web'])->group(function () {
    Route::get('/my-page', [MyController::class, 'show'])->name('my-page');
    Route::post('/my-page/update', [MyController::class, 'update'])->name('my-page.update');
});
```

#### 認証が不要なルート

```php
Route::get('/public-page', [PublicController::class, 'show'])->name('public-page');
```

#### 管理者専用ルート

```php
Route::middleware(['auth:admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});
```

---

### 2. 認証チェック付きメソッド作成

```php
// コントローラー内
public function store(Request $request)
{
    // 認証確認
    if (!Auth::check()) {
        return response()->json(['error' => '認証が必要です'], 401);
    }

    $user = Auth::user();
    $userId = Auth::id();

    // 処理...
}
```

---

### 3. ミドルウェアカスタム作成

```php
// app/Http/Middleware/CheckGrade.php
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckGrade
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->admission_year < 2024) {
            return response()->json(['error' => '受験資格がありません'], 403);
        }

        return $next($request);
    }
}
```

**登録**:

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    'check-grade' => \App\Http\Middleware\CheckGrade::class,
];

// routes/web.php
Route::middleware(['auth:web', 'check-grade'])->group(function () {
    Route::get('/exam', [ExamController::class, 'index']);
});
```

---

### 4. Inertia Props のカスタマイズ

```php
// HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            'userRole' => $request->user()?->role ?? 'guest',
        ],
        'appSettings' => [
            'timezone' => config('app.timezone'),
            'locale' => app()->getLocale(),
        ],
        'csrf_token' => $request->session()->token(),
        'flash' => [
            'message' => $request->session()->get('message'),
            'status' => $request->session()->get('status'),
        ],
    ]);
}
```

---

### 5. CSRF トークン削除対象ルート

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/webhook/*',  // Webhook は CSRF 不要
    'webhooks/*',
];
```

---

## 📝 テストの書き方

### 認証テスト

```php
// tests/Feature/AuthTest.php
<?php
namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/exam');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_protected_route()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/exam');
        $response->assertOk();
    }

    public function test_guest_cannot_access_protected_route()
    {
        $response = $this->get('/exam');
        $response->assertRedirect('/login');
    }
}
```

---

### CSRF トークンテスト

```php
public function test_csrf_token_validation()
{
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ], [
        'X-CSRF-TOKEN' => 'invalid-token',
    ]);

    $response->assertStatus(419);  // Token Mismatch
}
```

---

## 🐛 よくあるエラーと対処法

### 1. 419 Token Mismatch エラー

**原因**:

- CSRF トークン有効期限切れ
- トークン不一致
- キャッシュ問題

**対処法**:

```bash
# キャッシュクリア
php artisan cache:clear

# セッションリセット
php artisan session:table
php artisan migrate

# ブラウザキャッシュクリア
Ctrl + Shift + Delete
```

---

### 2. Auth::check() が false を返す

**原因**:

- セッション無効
- ユーザーIDが保存されていない
- ガード設定エラー

**対処法**:

```php
// Debug
dd(Auth::check());           // false?
dd(Auth::user());            // null?
dd(session()->all());        // user_id あるか?
dd(Auth::guard('web')->check());  // ガード指定
```

---

### 3. ログイン後もゲスト状態

**原因**:

- session_path の設定エラー
- session_driver が 'array' に設定
- ユーザーIDが Session に保存されていない

**対処法**:

```php
// .env 確認
SESSION_DRIVER=database  // 'array' ではなく
SESSION_LIFETIME=120

// config/session.php 確認
'driver' => env('SESSION_DRIVER', 'database'),
```

---

## 📚 参考リソース

### Laravel 公式ドキュメント

- [Authentication](https://laravel.com/docs/authentication)
- [Authorization](https://laravel.com/docs/authorization)
- [CSRF Protection](https://laravel.com/docs/csrf)
- [Sessions](https://laravel.com/docs/session)

### Inertia.js ドキュメント

- [Authentication](https://inertiajs.com/authentication)
- [CSRF Protection](https://inertiajs.com/security)

### Vue 3 ドキュメント

- [Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Reactivity](https://vuejs.org/guide/extras/reactivity-in-depth.html)

---

**最終更新**: 2025年12月9日  
**バージョン**: 1.0
