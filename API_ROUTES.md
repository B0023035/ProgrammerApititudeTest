# API ルート・コントローラー仕様

## 🛣️ ルート構成

### ルートファイル一覧

- `routes/web.php` - メインルート (Web SPA)
- `routes/auth.php` - 認証関連ルート
- `routes/api.php` - API ルート (未使用)

---

## 📍 Web ルート (`routes/web.php`)

### 認証なし・ゲストアクセス可能

#### 1. ホームページ・セッションコード入力

```
GET  /
POST /session-code/verify
```

**コントローラー**: `SessionCodeController`

**説明**: セッションコード入力・検証画面

**クエリ例**:

```
GET / → Welcome.vue (セッションコード入力)
POST /session-code/verify → イベント情報検証
```

---

#### 2. ゲスト試験フロー

```
GET  /guest/info
POST /guest/info/submit
POST /guest/exam/start
GET  /guest/exam/part/{part}
POST /guest/exam/part/{part}/answer
GET  /guest/result
```

**コントローラー**: `GuestExamController`

**説明**: ゲストユーザー用試験実施フロー

**パラメータ**:

- `{part}`: パート番号 (1, 2, 3)

**フロー**:

```
1. /guest/info → ゲスト情報入力
2. /guest/info/submit → 情報保存
3. /guest/exam/start → セッション開始
4. /guest/exam/part/1 → パート1表示
5. /guest/exam/part/1/answer → 解答送信 (POST)
6. /guest/result → 結果表示
```

---

### 認証が必要（ユーザー）

#### 3. ユーザーログイン

```
GET  /login
POST /login
GET  /logout
```

**コントローラー**: `AuthController`

**ミドルウェア**: `guest` (ログイン済みはリダイレクト)

**説明**: 一般ユーザーのログイン・ログアウト

**例**:

```
GET /login → Login.vue (ログインフォーム)
POST /login → 認証処理
```

**リクエストボディ** (POST /login):

```json
{
    "email": "user@example.com",
    "password": "password123",
    "remember": false
}
```

---

#### 4. ユーザー登録

```
GET  /register
POST /register
```

**コントローラー**: `AuthController`

**ミドルウェア**: `guest`

**説明**: 一般ユーザーの登録

**リクエストボディ** (POST /register):

```json
{
    "name": "田中太郎",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "admission_year": 2024
}
```

---

#### 5. 練習問題フロー

```
GET  /practice/{section}
GET  /practice/explanation/{part}
```

**コントローラー**: `PracticeController`

**ミドルウェア**: `auth:web`

**説明**: 練習問題の表示・解説表示

**パラメータ**:

- `{section}`: セクション番号 (1, 2, 3)
- `{part}`: パート番号 (1, 2, 3)

**例**:

```
GET /practice/1 → Part 1 の練習問題表示
GET /practice/explanation/1 → Part 1 の解説表示
```

---

#### 6. 本番試験フロー

```
POST /exam/start
GET  /exam/part/{part}
POST /exam/part/{part}/answer
GET  /exam/result/{uuid}
```

**コントローラー**: `ExamController`

**ミドルウェア**: `auth:web`, `check-session-code`

**説明**: 本番試験の実施

**エンドポイント詳細**:

##### 6.1 試験開始

```
POST /exam/start
```

**リクエストボディ**:

```json
{}
```

**レスポンス**:

```json
{
    "message": "試験開始",
    "redirect": "/exam/part/1"
}
```

**コントローラーメソッド**: `ExamController@start()`

**処理内容**:

1. セッション既存確認
2. 新規セッション作成または既存セッション再開
3. パート1へリダイレクト

---

##### 6.2 パート表示

```
GET /exam/part/{part}
```

**パラメータ**:

- `{part}`: 1, 2, 3

**クエリパラメータ**:

- `session_uuid`: セッション UUID (必須)

**レスポンス** (Inertia):

```js
{
    "questions": [
        {
            "id": 1,
            "part": 1,
            "number": 1,
            "text": "問題文",
            "choices": [
                {
                    "id": 1,
                    "label": "A",
                    "text": "選択肢A"
                },
                // ... 他選択肢
            ]
        },
        // ... 他問題
    ],
    "currentQuestion": 0,
    "currentPart": 1,
    "remainingTime": 3600,
    "totalQuestions": 20,
    "sessionUuid": "uuid-string"
}
```

**コントローラーメソッド**: `ExamController@part($part)`

---

##### 6.3 解答送信

```
POST /exam/part/{part}/answer
```

**リクエストボディ**:

```json
{
    "question_id": 1,
    "choice": "A",
    "session_uuid": "uuid-string"
}
```

**レスポンス**:

```json
{
    "success": true,
    "message": "解答を保存しました",
    "next_part": 2
}
```

**コントローラーメソッド**: `ExamController@submitAnswer()`

**処理内容**:

1. セッション検証
2. 解答の正誤判定
3. 解答を記録
4. 不正検知チェック

---

##### 6.4 結果表示

```
GET /exam/result/{uuid}
```

**パラメータ**:

- `{uuid}`: セッション UUID

**レスポンス** (Inertia):

```js
{
    "session": {
        "id": 1,
        "user": { "name": "田中太郎" },
        "event": { "name": "2025年1月入学試験" },
        "started_at": "2025-01-15T10:00:00",
        "finished_at": "2025-01-15T13:30:00"
    },
    "results": {
        "part1": {
            "total": 20,
            "correct": 18,
            "percentage": 90
        },
        "part2": {
            "total": 25,
            "correct": 22,
            "percentage": 88
        },
        "part3": {
            "total": 20,
            "correct": 15,
            "percentage": 75
        },
        "overall": {
            "total": 65,
            "correct": 55,
            "percentage": 84.6
        }
    },
    "violations": [
        {
            "type": "tab_switch",
            "timestamp": "2025-01-15T10:15:00"
        }
    ]
}
```

**コントローラーメソッド**: `ExamController@result($uuid)`

---

### 管理者専用ルート

#### 7. 管理者ログイン

```
GET  /admin/login
POST /admin/login
```

**コントローラー**: `Admin\AdminAuthController`

**ガード**: `admin`

**ミドルウェア**: `guest:admin`

**例**:

```
GET /admin/login → Admin Login.vue
POST /admin/login → 認証処理
```

---

#### 8. 管理者ダッシュボード

```
GET /admin/dashboard
```

**コントローラー**: `Admin\DashboardController`

**ミドルウェア**: `auth:admin`

**説明**: 管理者ダッシュボード表示

---

#### 9. イベント管理

```
GET    /admin/events
GET    /admin/events/create
POST   /admin/events
GET    /admin/events/{event}/edit
PUT    /admin/events/{event}
DELETE /admin/events/{event}
```

**コントローラー**: `Admin\EventManagementController`

**ミドルウェア**: `auth:admin`

**説明**: 試験イベントの作成・編集・削除

---

#### 10. 問題管理

```
GET    /admin/questions
GET    /admin/questions/create
POST   /admin/questions
GET    /admin/questions/{question}/edit
PUT    /admin/questions/{question}
DELETE /admin/questions/{question}
```

**コントローラー**: `Admin\QuestionManagementController`

**ミドルウェア**: `auth:admin`

**説明**: 試験問題の作成・編集・削除

---

#### 11. 成績管理

```
GET /admin/results
GET /admin/results/export
GET /admin/results/session/{uuid}
```

**コントローラー**: `Admin\ResultsManagementController`

**ミドルウェア**: `auth:admin`

**説明**: 試験成績の確認・エクスポート

---

## 🎮 コントローラー詳細

### ExamController

**ファイル**: `app/Http/Controllers/ExamController.php`

**用途**: 本番試験のメイン処理

#### メソッド一覧

##### 1. `start(Request $request): Response`

**説明**: 本番試験開始

**処理フロー**:

```
1. セッションコード確認
2. イベント情報取得
3. 既存セッション確認
   ├─ あり → 既存セッションを再開
   └─ なし → 新規セッション作成
4. パート1へリダイレクト
```

**コード例**:

```php
public function start(Request $request): Response
{
    $sessionCode = session('session_code');
    $event = Event::where('passphrase', $sessionCode)
        ->where('begin', '<=', now())
        ->where('end', '>=', now())
        ->firstOrFail();

    $session = ExamSession::updateOrCreate(
        [
            'user_id' => Auth::id(),
            'event_id' => $event->id,
        ],
        [
            'session_uuid' => Str::uuid(),
            'grade' => Auth::user()->admission_year,
            'started_at' => now(),
            'current_part' => 1,
            'remaining_time' => $event->part1_time,
        ]
    );

    return redirect()->route('exam.part', ['part' => 1]);
}
```

---

##### 2. `part(int $part, Request $request): Response`

**説明**: パート表示

**パラメータ**:

- `$part`: パート番号 (1, 2, 3)

**クエリパラメータ**:

- `session_uuid`: セッション UUID

**処理フロー**:

```
1. セッションコード確認
2. セッション検証
3. パート有効期限確認
4. 問題と選択肢を取得
5. Inertia で Vue コンポーネント返送
```

**コード例**:

```php
public function part(int $part, Request $request): Response
{
    $sessionUuid = $request->query('session_uuid');

    $session = ExamSession::where('session_uuid', $sessionUuid)
        ->where('user_id', Auth::id())
        ->where('disqualified_at', null)
        ->firstOrFail();

    // 時間チェック
    if ($session->remaining_time <= 0) {
        return redirect()->route('exam.result', ['uuid' => $sessionUuid]);
    }

    $questions = Question::where('part', $part)
        ->with('choices')
        ->get();

    return Inertia::render('Part', [
        'questions' => $questions,
        'currentPart' => $part,
        'sessionUuid' => $sessionUuid,
        'remainingTime' => $session->remaining_time,
    ]);
}
```

---

##### 3. `submitAnswer(Request $request): Response`

**説明**: 解答送信

**リクエスト検証**:

```php
$request->validate([
    'question_id' => 'required|integer|exists:questions,id',
    'choice' => 'required|string|in:A,B,C,D,E',
    'session_uuid' => 'required|string',
]);
```

**処理フロー**:

```
1. セッション検証
2. 問題と正答を確認
3. 正誤判定
4. 解答を記録
5. 不正検知チェック
6. 次のパートまたは結果へ
```

---

##### 4. `result(string $uuid): Response`

**説明**: 試験結果表示

**処理内容**:

```
1. セッション取得
2. 全パート成績集計
3. 違反記録を確認
4. 結果を Inertia で返送
```

---

### PracticeController

**ファイル**: `app/Http/Controllers/PracticeController.php`

**用途**: 練習問題の表示・管理

#### メソッド一覧

##### 1. `show(int $section): Response`

**説明**: 練習問題表示

**処理内容**:

```
1. セクション確認
2. 練習問題を取得
3. Vue コンポーネント返送
```

---

##### 2. `explanation(int $part): Response`

**説明**: 練習問題解説表示

**パラメータ**:

- `$part`: パート番号

**処理内容**:

```
1. 練習問題をパート別に取得
2. 解説を含めて返送
```

---

### SessionCodeController

**ファイル**: `app/Http/Controllers/SessionCodeController.php`

**用途**: セッションコード検証

#### メソッド一覧

##### 1. `verify(Request $request): Response`

**説明**: セッションコード検証

**リクエスト検証**:

```php
$request->validate([
    'session_code' => 'required|string',
]);
```

**処理内容**:

```
1. セッションコード検証
2. イベント情報取得
3. セッションに保存
4. ゲスト/認証済みユーザーを判別
5. 適切なページへリダイレクト
```

---

## 🔐 ミドルウェア一覧

### 1. CheckSessionCode

**ファイル**: `app/Http/Middleware/CheckSessionCode.php`

**用途**: セッションコードの検証

**処理内容**:

```
1. セッションにセッションコードがあるか確認
2. コードの有効期限を確認
3. イベント情報を検証
```

---

### 2. HandleInertiaRequests

**ファイル**: `app/Http/Middleware/HandleInertiaRequests.php`

**用途**: Inertia の Props 共有

**共有データ**:

```php
return [
    'auth' => [
        'user' => $request->user(),
    ],
    'isAdmin' => Auth::guard('admin')->check(),
    'csrf_token' => $request->session()->token(),
    'ziggy' => function () {
        return array_merge((new Ziggy())->toArray(), [
            'location' => $request->url(),
        ]);
    },
];
```

---

## 📊 リクエスト・レスポンス例

### 例1: ゲストユーザーが試験開始

#### ステップ1: セッションコード入力

```
POST /session-code/verify
Content-Type: application/json

{
    "session_code": "EXAM2025"
}

Response:
201 Created
{
    "message": "セッションコード検証成功",
    "event": {
        "name": "2025年1月入学試験",
        "begin": "2025-01-15T09:00:00",
        "end": "2025-01-15T15:00:00",
        "exam_type": "full"
    }
}
```

#### ステップ2: ゲスト情報入力

```
POST /guest/info/submit
Content-Type: application/json

{
    "name": "山田花子",
    "grade": "高3",
    "school": "○○高等学校"
}

Response:
200 OK
{
    "message": "ゲスト情報を保存しました"
}
```

#### ステップ3: 試験開始

```
POST /guest/exam/start
Content-Type: application/json

{}

Response:
200 OK (Inertia response)
Component: Part
Props: {
    "questions": [...],
    "currentPart": 1,
    "sessionUuid": "xxx-xxx-xxx"
}
```

---

### 例2: 認証ユーザーが解答送信

```
POST /exam/part/1/answer
Content-Type: application/json
X-CSRF-TOKEN: token123

{
    "question_id": 5,
    "choice": "B",
    "session_uuid": "xxx-xxx-xxx"
}

Response:
200 OK
{
    "success": true,
    "message": "解答を保存しました",
    "isCorrect": true
}
```

---

### 例3: 成績確認

```
GET /exam/result/xxx-xxx-xxx

Response:
200 OK (Inertia response)
Component: Result
Props: {
    "session": {...},
    "results": {
        "part1": {"total": 20, "correct": 18, "percentage": 90},
        "part2": {"total": 25, "correct": 22, "percentage": 88},
        "part3": {"total": 20, "correct": 15, "percentage": 75},
        "overall": {"total": 65, "correct": 55, "percentage": 84.6}
    },
    "violations": [...]
}
```

---

## 🔄 HTTP ステータスコード

| コード | 説明                  | シーン               |
| ------ | --------------------- | -------------------- |
| 200    | OK                    | リクエスト成功       |
| 201    | Created               | リソース作成成功     |
| 400    | Bad Request           | リクエスト形式エラー |
| 401    | Unauthorized          | 認証失敗             |
| 403    | Forbidden             | 権限なし             |
| 404    | Not Found             | リソースなし         |
| 419    | Token Mismatch        | CSRF トークン無効    |
| 422    | Unprocessable Entity  | バリデーションエラー |
| 500    | Internal Server Error | サーバーエラー       |

---

**最終更新**: 2025年12月9日
