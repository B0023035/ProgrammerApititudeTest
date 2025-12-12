# モデル関係図・データ仕様

## 🔗 モデル関係図 (ER Diagram)

### テキスト形式の関係図

```
┌─────────────────────────────────────────────────────────────────────┐
│                        ユーザー認証層                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────┐          ┌──────────────┐                        │
│  │   users      │          │    admins    │                        │
│  │ (Guard:web)  │          │(Guard:admin) │                        │
│  └──────────────┘          └──────────────┘                        │
│         │                         │                                 │
│         │ (ユーザー認証)          │ (管理者認証)                      │
│         │                         │                                 │
└─────────────────────────────────────────────────────────────────────┘
        │
        │ 1 : N
        │
┌───────┴─────────────────────────────────────────────────────────┐
│                    試験セッション・管理層                          │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────┐                                       │
│  │  exam_sessions       │                                       │
│  │ - session_uuid       │                                       │
│  │ - user_id (FK)       │────────┐                             │
│  │ - event_id (FK)      │────────┼──────────┐                 │
│  │ - grade              │        │          │                 │
│  │ - current_part       │        │          │                 │
│  │ - remaining_time     │        │          │                 │
│  │ - security_log (JSON)│        │          │                 │
│  └──────────────────────┘        │          │                 │
│         │                         │          │                 │
│         │ 1 : N                   │          │                 │
│         ├─────────────────────┐   │          │                 │
│         │                     │   │          │                 │
│    ┌────┴─────────┐     ┌─────┴──┴────┐     │                 │
│    │   answers    │     │    events   │     │                 │
│    │ - user_id    │     │ - name      │     │                 │
│    │ - question_id│     │ - passphrase│     │                 │
│    │ - choice     │     │ - begin     │     │                 │
│    │ - is_correct │     │ - end       │     │                 │
│    └────┬─────────┘     │ - exam_type │     │                 │
│         │                │ - part{1,2,3} │  │                 │
│         │ N : 1          └─────┬────────┘  │                 │
│         │                      │            │                 │
│         └──────────┬───────────┘            │                 │
│                    │                        │                 │
│              ┌─────┴────────┐               │                 │
│              │   questions  │               │                 │
│              │ - part       │               │                 │
│              │ - number     │               │                 │
│              │ - text       │               │                 │
│              │ - image      │               │                 │
│              └─────┬────────┘               │                 │
│                    │                        │                 │
│                    │ 1 : N                  │                 │
│                    │                        │                 │
│              ┌─────┴────────┐               │                 │
│              │   choices    │               │                 │
│              │ - label      │               │                 │
│              │ - text       │               │                 │
│              │ - image      │               │                 │
│              │ - is_correct │               │                 │
│              └──────────────┘               │                 │
│                                              │                 │
│  ┌──────────────────────┐                   │                 │
│  │  exam_violations     │───────────────────┘                 │
│  │ - exam_session_id    │                                     │
│  │ - violation_type     │                                     │
│  │ - details (JSON)     │                                     │
│  └──────────────────────┘                                     │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                        練習問題層                                  │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────┐                                       │
│  │  practice_questions  │                                       │
│  │ - section            │                                       │
│  │ - question           │                                       │
│  │ - options (JSON)     │                                       │
│  │ - answer             │                                       │
│  │ - explanation        │                                       │
│  └──────────────────────┘                                       │
│         │                                                       │
│         │ 1 : N                                                │
│         │                                                       │
│  ┌──────┴─────────────────┐                                     │
│  │  practice_choices      │                                     │
│  │ - practice_question_id │                                     │
│  │ - label                │                                     │
│  │ - text                 │                                     │
│  │ - is_correct           │                                     │
│  └────────────────────────┘                                     │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 📋 モデル詳細仕様

### 1. User モデル

**用途**: 一般ユーザー認証・管理

**継承**: `Authenticatable`, `Notifiable`

**ガード**: `web`

**テーブル**: `users`

#### フィールド

| カラム名          | 型        | 必須 | 説明                    |
| ----------------- | --------- | ---- | ----------------------- |
| id                | bigint    | ✓    | 主キー                  |
| name              | string    | ✓    | ユーザー名              |
| email             | string    | ✓    | メールアドレス (UNIQUE) |
| password          | string    | ✓    | ハッシュ化パスワード    |
| admission_year    | integer   | ○    | 入学年度                |
| email_verified_at | timestamp | ○    | メール確認日時          |
| remember_token    | string    | ○    | 自動ログイントークン    |
| created_at        | timestamp | ✓    | 作成日時                |
| updated_at        | timestamp | ✓    | 更新日時                |

#### リレーション

```php
public function examSessions(): HasMany
    // 1ユーザー : N セッション

public function answers(): HasMany
    // 1ユーザー : N 解答
```

#### キャスト

```php
protected $casts = [
    'password' => 'hashed',
    'email_verified_at' => 'datetime',
];
```

#### 例

```php
$user = User::find(1);
$user->examSessions;  // ユーザーの試験セッション一覧
$user->answers;       // ユーザーの全解答
```

---

### 2. Admin モデル

**用途**: 管理者認証・管理画面アクセス

**継承**: `Authenticatable`

**ガード**: `admin`

**テーブル**: `admins`

#### フィールド

| カラム名       | 型        | 必須 | 説明                    |
| -------------- | --------- | ---- | ----------------------- |
| id             | bigint    | ✓    | 主キー                  |
| name           | string    | ✓    | 管理者名                |
| email          | string    | ✓    | メールアドレス (UNIQUE) |
| password       | string    | ✓    | ハッシュ化パスワード    |
| remember_token | string    | ○    | 自動ログイントークン    |
| created_at     | timestamp | ✓    | 作成日時                |
| updated_at     | timestamp | ✓    | 更新日時                |

#### 使用例

```php
// 管理者ログイン
Auth::guard('admin')->attempt(['email' => $email, 'password' => $password]);

// ログイン確認
Auth::guard('admin')->check();

// 現在の管理者情報
Auth::guard('admin')->user();
```

---

### 3. ExamSession モデル

**用途**: 試験セッション・進行状況管理

**テーブル**: `exam_sessions`

#### フィールド

| カラム名                | 型        | 必須 | 説明                   |
| ----------------------- | --------- | ---- | ---------------------- |
| id                      | bigint    | ✓    | 主キー                 |
| user_id                 | bigint    | ✓    | ユーザーID (FK→users)  |
| event_id                | bigint    | ✓    | イベントID (FK→events) |
| session_uuid            | string    | ✓    | セッション識別用UUID   |
| grade                   | integer   | ✓    | 受験時の学年           |
| started_at              | timestamp | ✓    | 試験開始時刻           |
| finished_at             | timestamp | ○    | 試験終了時刻           |
| disqualified_at         | timestamp | ○    | 失格時刻               |
| disqualification_reason | string    | ○    | 失格理由               |
| current_part            | integer   | ✓    | 現在のパート (1,2,3)   |
| current_question        | integer   | ○    | 現在の問題番号         |
| remaining_time          | integer   | ✓    | 残り時間(秒)           |
| security_log            | json      | ○    | セキュリティログ       |
| created_at              | timestamp | ✓    | 作成日時               |
| updated_at              | timestamp | ✓    | 更新日時               |

#### リレーション

```php
public function user(): BelongsTo
    // N セッション : 1 ユーザー

public function event(): BelongsTo
    // N セッション : 1 イベント

public function answers(): HasMany
    // 1 セッション : N 解答

public function violations(): HasMany
    // 1 セッション : N 違反記録
```

#### キャスト

```php
protected $casts = [
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'disqualified_at' => 'datetime',
    'security_log' => 'array',
];
```

#### 例

```php
$session = ExamSession::with('user', 'event', 'answers')->find(1);
$session->answers()->whereCorrect(true)->count();  // 正答数
$session->violations->count();  // 違反件数
```

---

### 4. Event モデル

**用途**: 試験イベント設定・スケジュール管理

**テーブル**: `events`

#### フィールド

| カラム名        | 型        | 必須 | 説明                                |
| --------------- | --------- | ---- | ----------------------------------- |
| id              | bigint    | ✓    | 主キー                              |
| name            | string    | ✓    | イベント名                          |
| passphrase      | string    | ✓    | セッションコード                    |
| begin           | timestamp | ✓    | 試験開始日時                        |
| end             | timestamp | ✓    | 試験終了日時                        |
| exam_type       | string    | ✓    | 試験タイプ (full/part1/part2/part3) |
| part1_questions | integer   | ✓    | パート1問題数                       |
| part1_time      | integer   | ✓    | パート1時間制限(秒)                 |
| part2_questions | integer   | ✓    | パート2問題数                       |
| part2_time      | integer   | ✓    | パート2時間制限(秒)                 |
| part3_questions | integer   | ✓    | パート3問題数                       |
| part3_time      | integer   | ✓    | パート3時間制限(秒)                 |
| created_at      | timestamp | ✓    | 作成日時                            |
| updated_at      | timestamp | ✓    | 更新日時                            |

#### リレーション

```php
public function examSessions(): HasMany
    // 1 イベント : N セッション

public function questions(): HasMany
    // 1 イベント : N 問題
```

#### キャスト

```php
protected $casts = [
    'begin' => 'datetime',
    'end' => 'datetime',
];
```

#### 例

```php
// 現在実施中のイベント取得
$event = Event::where('begin', '<=', now())
    ->where('end', '>=', now())
    ->first();

// イベント情報
$event->part1_time;  // パート1時間制限
$event->exam_type;   // 試験タイプ
```

---

### 5. Question モデル

**用途**: 本番試験問題管理

**テーブル**: `questions`

#### フィールド

| カラム名   | 型        | 必須 | 説明               |
| ---------- | --------- | ---- | ------------------ |
| id         | bigint    | ✓    | 主キー             |
| part       | integer   | ✓    | パート (1,2,3)     |
| number     | integer   | ✓    | 問題番号           |
| text       | longtext  | ✓    | 問題文             |
| image      | string    | ○    | 問題画像ファイル名 |
| created_at | timestamp | ✓    | 作成日時           |
| updated_at | timestamp | ✓    | 更新日時           |

#### リレーション

```php
public function choices(): HasMany
    // 1 問題 : N 選択肢

public function answers(): HasMany
    // 1 問題 : N 解答
```

#### インデックス

```
- (part, number) - UNIQUE (パート内の問題番号は一意)
```

#### 例

```php
// パート1の全問題を取得
$questions = Question::where('part', 1)
    ->orderBy('number')
    ->with('choices')
    ->get();

// 特定の問題と選択肢を取得
$question = Question::with('choices')
    ->where('part', 1)
    ->where('number', 1)
    ->first();
```

---

### 6. Choice モデル

**用途**: 問題の選択肢管理

**テーブル**: `choices`

#### フィールド

| カラム名    | 型        | 必須 | 説明                     |
| ----------- | --------- | ---- | ------------------------ |
| id          | bigint    | ✓    | 主キー                   |
| question_id | bigint    | ✓    | 問題ID (FK→questions)    |
| part        | integer   | ✓    | パート (1,2,3)           |
| label       | string    | ✓    | 選択肢ラベル (A,B,C,D,E) |
| text        | longtext  | ✓    | 選択肢テキスト           |
| image       | string    | ○    | 選択肢画像ファイル名     |
| is_correct  | boolean   | ✓    | 正解判定                 |
| created_at  | timestamp | ✓    | 作成日時                 |
| updated_at  | timestamp | ✓    | 更新日時                 |

#### リレーション

```php
public function question(): BelongsTo
    // N 選択肢 : 1 問題
```

#### 例

```php
// 問題の全選択肢を取得
$choices = Choice::where('question_id', 1)
    ->orderBy('label')
    ->get();

// 正解を取得
$correct = Choice::where('question_id', 1)
    ->where('is_correct', true)
    ->first();

// ユーザーの解答
$choice = Choice::find($user_choice_id);
$choice->is_correct;  // true/false
```

---

### 7. Answer モデル

**用途**: ユーザーの解答記録

**テーブル**: `answers`

#### フィールド

| カラム名        | 型        | 必須 | 説明                            |
| --------------- | --------- | ---- | ------------------------------- |
| id              | bigint    | ✓    | 主キー                          |
| user_id         | bigint    | ✓    | ユーザーID (FK→users)           |
| exam_session_id | bigint    | ✓    | セッションID (FK→exam_sessions) |
| question_id     | bigint    | ✓    | 問題ID (FK→questions)           |
| part            | integer   | ✓    | パート (1,2,3)                  |
| choice          | string    | ✓    | 選択された選択肢 (A,B,C,D,E)    |
| is_correct      | boolean   | ✓    | 正解判定                        |
| created_at      | timestamp | ✓    | 作成日時                        |
| updated_at      | timestamp | ✓    | 更新日時                        |

#### リレーション

```php
public function user(): BelongsTo
    // N 解答 : 1 ユーザー

public function examSession(): BelongsTo
    // N 解答 : 1 セッション

public function question(): BelongsTo
    // N 解答 : 1 問題
```

#### インデックス

```
- (exam_session_id, question_id) - UNIQUE (セッション内での重複を防止)
```

#### 例

```php
// セッションの全解答を取得
$answers = Answer::where('exam_session_id', $sessionId)
    ->with('question', 'question.choices')
    ->get();

// パート1の正答数
$correctCount = Answer::where('exam_session_id', $sessionId)
    ->where('part', 1)
    ->where('is_correct', true)
    ->count();

// ユーザーの特定問題への解答
$answer = Answer::where('user_id', $userId)
    ->where('question_id', $questionId)
    ->latest()
    ->first();
```

---

### 8. PracticeQuestion モデル

**用途**: 練習問題管理

**テーブル**: `practice_questions`

#### フィールド

| カラム名    | 型        | 必須 | 説明               |
| ----------- | --------- | ---- | ------------------ |
| id          | bigint    | ✓    | 主キー             |
| section     | integer   | ✓    | セクション (1,2,3) |
| question    | text      | ✓    | 問題文             |
| options     | json      | ✓    | 選択肢配列         |
| answer      | string    | ✓    | 正答               |
| explanation | text      | ✓    | 解説               |
| created_at  | timestamp | ✓    | 作成日時           |
| updated_at  | timestamp | ✓    | 更新日時           |

#### リレーション

```php
public function choices(): HasMany
    // 1 問題 : N 選択肢
```

#### キャスト

```php
protected $casts = [
    'options' => 'array',
];
```

#### 例

```php
// セクション1の練習問題を取得
$practices = PracticeQuestion::where('section', 1)->get();

// 特定の練習問題の選択肢
$problem = PracticeQuestion::find(1);
$problem->options;  // ["A", "B", "C", "D", "E"]
$problem->answer;   // "A" など
```

---

### 9. PracticeChoice モデル

**用途**: 練習問題の選択肢管理

**テーブル**: `practice_choices`

#### フィールド

| カラム名             | 型        | 必須 | 説明                     |
| -------------------- | --------- | ---- | ------------------------ |
| id                   | bigint    | ✓    | 主キー                   |
| practice_question_id | bigint    | ✓    | 練習問題ID               |
| label                | string    | ✓    | 選択肢ラベル (A,B,C,D,E) |
| text                 | text      | ✓    | 選択肢テキスト           |
| is_correct           | boolean   | ✓    | 正解判定                 |
| created_at           | timestamp | ✓    | 作成日時                 |
| updated_at           | timestamp | ✓    | 更新日時                 |

#### リレーション

```php
public function practiceQuestion(): BelongsTo
    // N 選択肢 : 1 練習問題
```

---

### 10. ExamViolation モデル

**用途**: セキュリティ違反・不正検知記録

**テーブル**: `exam_violations`

#### フィールド

| カラム名        | 型        | 必須 | 説明              |
| --------------- | --------- | ---- | ----------------- |
| id              | bigint    | ✓    | 主キー            |
| exam_session_id | bigint    | ✓    | セッションID (FK) |
| violation_type  | string    | ✓    | 違反タイプ        |
| details         | json      | ✓    | 詳細情報          |
| created_at      | timestamp | ✓    | 作成日時          |

#### リレーション

```php
public function examSession(): BelongsTo
    // N 違反 : 1 セッション
```

#### 違反タイプ例

- `window_blur` - ウィンドウがフォーカスを失った
- `tab_switch` - タブ切り替え検出
- `keyboard_shortcut` - キーボードショートカット使用
- `screen_capture` - 画面キャプチャ試行
- `copy_paste` - コピー・ペースト試行

#### 例

```php
// セッションの違反一覧
$violations = ExamViolation::where('exam_session_id', $sessionId)->get();

// 違反件数による失格判定
if ($violations->count() >= 3) {
    $session->update([
        'disqualified_at' => now(),
        'disqualification_reason' => 'Multiple violations detected'
    ]);
}
```

---

## 🔄 リレーション検索パターン集

### 1. ユーザーの試験成績一覧

```php
$user = User::with([
    'examSessions' => function ($query) {
        $query->with(['event', 'answers']);
    }
])->find($userId);

foreach ($user->examSessions as $session) {
    $correctCount = $session->answers()
        ->where('is_correct', true)
        ->count();
    $totalCount = $session->answers()->count();
    $percentage = ($correctCount / $totalCount) * 100;
}
```

### 2. イベントの全解答を取得

```php
$answers = Answer::whereHas('examSession', function ($query) use ($eventId) {
    $query->where('event_id', $eventId);
})
->with(['user', 'question', 'examSession'])
->get();
```

### 3. 問題別の正答率計算

```php
$question = Question::with('answers')->find($questionId);

$totalAttempts = $question->answers()->count();
$correctAttempts = $question->answers()
    ->where('is_correct', true)
    ->count();

$correctRate = $totalAttempts > 0
    ? ($correctAttempts / $totalAttempts) * 100
    : 0;
```

### 4. ユーザーの不正検知情報

```php
$violations = ExamViolation::whereHas('examSession', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})
->with('examSession')
->orderByDesc('created_at')
->get();
```

### 5. パート別成績の集計

```php
$partStats = Answer::whereHas('examSession', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})
->selectRaw('part, COUNT(*) as total, SUM(is_correct) as correct')
->groupBy('part')
->get();

foreach ($partStats as $stat) {
    $percentage = ($stat->correct / $stat->total) * 100;
}
```

---

## 📊 データモデルの使用シーン

### シーン1: ユーザーが試験にエントリー

```php
// 1. イベント検証
$event = Event::where('passphrase', $sessionCode)->firstOrFail();

// 2. セッション作成
$session = ExamSession::create([
    'user_id' => Auth::id(),
    'event_id' => $event->id,
    'session_uuid' => Str::uuid(),
    'grade' => Auth::user()->admission_year,
    'current_part' => 1,
    'remaining_time' => $event->part1_time,
]);

// 3. 問題取得
$questions = Question::where('part', 1)
    ->with('choices')
    ->limit($event->part1_questions)
    ->get();
```

### シーン2: ユーザーが解答を送信

```php
// 1. セッション検証
$session = ExamSession::where('session_uuid', $sessionUuid)
    ->where('user_id', Auth::id())
    ->where('disqualified_at', null)
    ->firstOrFail();

// 2. 問題と正答を確認
$question = Question::with('choices')->find($questionId);
$correctChoice = $question->choices()
    ->where('is_correct', true)
    ->first();

// 3. 解答を記録
$answer = Answer::updateOrCreate(
    [
        'exam_session_id' => $session->id,
        'question_id' => $questionId,
    ],
    [
        'user_id' => Auth::id(),
        'part' => $session->current_part,
        'choice' => $userChoice,
        'is_correct' => $userChoice === $correctChoice->label,
    ]
);

// 4. 成績更新（自動計算）
```

### シーン3: 管理者が成績を確認

```php
// イベント別集計
$eventResults = ExamSession::where('event_id', $eventId)
    ->with(['user', 'answers'])
    ->get()
    ->map(function ($session) {
        return [
            'user_name' => $session->user->name,
            'correct_count' => $session->answers()
                ->where('is_correct', true)
                ->count(),
            'total_count' => $session->answers()->count(),
            'grade' => $session->grade,
            'violations' => $session->violations()->count(),
        ];
    });
```

---

**最終更新**: 2025年12月9日
