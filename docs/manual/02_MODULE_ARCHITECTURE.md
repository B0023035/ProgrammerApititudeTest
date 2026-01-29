# モジュール構成図

プログラマー適性試験システムのモジュール構成を説明します。

## 全体構成

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ProgrammerAptitudeTest                               │
│                        プログラマー適性試験システム                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                     フロントエンド (Vue.js 3)                         │    │
│  │  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐            │    │
│  │  │ Guest Pages   │  │ User Pages    │  │ Admin Pages   │            │    │
│  │  │ (ゲスト向け)   │  │ (受験者向け)   │  │ (管理者向け)   │            │    │
│  │  └───────────────┘  └───────────────┘  └───────────────┘            │    │
│  │                          │                                           │    │
│  │  ┌─────────────────────────────────────────────────────────────────┐│    │
│  │  │                   Inertia.js (SPA通信)                          ││    │
│  │  └─────────────────────────────────────────────────────────────────┘│    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                    │                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                    バックエンド (Laravel 11)                         │    │
│  │  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐            │    │
│  │  │ Controllers   │  │ Models        │  │ Middleware    │            │    │
│  │  └───────────────┘  └───────────────┘  └───────────────┘            │    │
│  │  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐            │    │
│  │  │ Services      │  │ Requests      │  │ Resources     │            │    │
│  │  └───────────────┘  └───────────────┘  └───────────────┘            │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                    │                                         │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │                      データ層                                        │    │
│  │  ┌───────────────────────────┐  ┌───────────────────────────┐      │    │
│  │  │      MySQL 8.0            │  │      Redis 7              │      │    │
│  │  │   (永続データ保存)          │  │  (セッション・キャッシュ)   │      │    │
│  │  └───────────────────────────┘  └───────────────────────────┘      │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## フロントエンド構成

### ディレクトリ構造

```
resources/js/
├── app.ts                    # アプリケーションエントリポイント
├── ssr.ts                    # SSR用エントリポイント
├── bootstrap.ts              # 初期設定
│
├── Components/               # 再利用可能コンポーネント
│   ├── AdminButton.vue       # 管理者用ボタン
│   ├── ExamHeader.vue        # 試験ヘッダー
│   ├── ExamProgress.vue      # 進捗表示
│   ├── Modal.vue             # モーダル
│   ├── Pagination.vue        # ページネーション
│   └── ...
│
├── Layouts/                  # レイアウトコンポーネント
│   ├── AdminLayout.vue       # 管理者用レイアウト
│   ├── AuthLayout.vue        # 認証用レイアウト
│   ├── GuestLayout.vue       # ゲスト用レイアウト
│   └── UserLayout.vue        # ユーザー用レイアウト
│
└── Pages/                    # ページコンポーネント
    ├── Admin/                # 管理者ページ
    │   ├── Dashboard.vue     # ダッシュボード
    │   ├── Events/           # イベント管理
    │   ├── Questions/        # 問題管理
    │   ├── Results/          # 成績管理
    │   ├── Accounts/         # アカウント管理
    │   ├── Statistics/       # 統計・グラフ
    │   └── Practice/         # 練習問題管理
    │
    ├── Guest/                # ゲスト向けページ
    │   ├── Home.vue          # トップページ
    │   ├── GuestEntry.vue    # ゲスト試験開始
    │   └── Practice/         # 練習問題
    │
    └── User/                 # 受験者ページ
        ├── Dashboard.vue     # ダッシュボード
        ├── Exam/             # 試験実施
        └── MyResults/        # 成績確認
```

---

## バックエンド構成

### ディレクトリ構造

```
app/
├── Console/                  # Artisanコマンド
│   └── Commands/
│
├── Http/
│   ├── Controllers/          # コントローラー
│   │   ├── Admin/            # 管理者用
│   │   │   ├── DashboardController.php
│   │   │   ├── EventController.php
│   │   │   ├── QuestionController.php
│   │   │   ├── ResultController.php
│   │   │   ├── AccountController.php
│   │   │   ├── StatisticsController.php
│   │   │   └── PracticeController.php
│   │   │
│   │   ├── Guest/            # ゲスト用
│   │   │   ├── HomeController.php
│   │   │   ├── GuestExamController.php
│   │   │   └── PracticeController.php
│   │   │
│   │   └── User/             # ユーザー用
│   │       ├── DashboardController.php
│   │       ├── ExamController.php
│   │       └── ResultController.php
│   │
│   ├── Middleware/           # ミドルウェア
│   │   ├── AdminAuth.php     # 管理者認証
│   │   ├── GuestUser.php     # ゲストユーザー
│   │   └── EnsureSessionStarted.php
│   │
│   └── Requests/             # フォームリクエスト
│       ├── Admin/
│       └── User/
│
├── Models/                   # Eloquentモデル
│   ├── Admin.php             # 管理者
│   ├── User.php              # ユーザー
│   ├── Event.php             # イベント
│   ├── Question.php          # 問題
│   ├── Choice.php            # 選択肢
│   ├── ExamSession.php       # 試験セッション
│   ├── Answer.php            # 回答
│   ├── PracticeQuestion.php  # 練習問題
│   └── PracticeChoice.php    # 練習選択肢
│
├── Services/                 # ビジネスロジック
│   ├── ExamService.php       # 試験サービス
│   ├── ScoringService.php    # 採点サービス
│   └── StatisticsService.php # 統計サービス
│
└── Providers/                # サービスプロバイダ
    └── AppServiceProvider.php
```

---

## モデル関連図

```
┌────────────────┐     ┌────────────────┐     ┌────────────────┐
│     Admin      │     │     Event      │     │    Question    │
├────────────────┤     ├────────────────┤     ├────────────────┤
│ id             │     │ id             │     │ id             │
│ name           │     │ name           │     │ question_text  │
│ email          │     │ passphrase     │     │ genre          │
│ password       │     │ begin          │     │ difficulty     │
│ role           │     │ end            │     │ points         │
└────────────────┘     └────────────────┘     └────────────────┘
                              │                      │
                              │ 1:N                  │ 1:N
                              ▼                      ▼
                       ┌────────────────┐     ┌────────────────┐
                       │ EventQuestion  │     │    Choice      │
                       ├────────────────┤     ├────────────────┤
                       │ event_id       │     │ question_id    │
                       │ question_id    │     │ choice_text    │
                       │ order          │     │ is_correct     │
                       └────────────────┘     └────────────────┘
                              
┌────────────────┐     ┌────────────────┐     ┌────────────────┐
│     User       │────▶│  ExamSession   │────▶│    Answer      │
├────────────────┤ 1:N ├────────────────┤ 1:N ├────────────────┤
│ id             │     │ id             │     │ id             │
│ name           │     │ user_id        │     │ session_id     │
│ email          │     │ event_id       │     │ question_id    │
│ password       │     │ total_score    │     │ choice_id      │
│ is_guest       │     │ rank           │     │ is_correct     │
└────────────────┘     │ finished_at    │     │ answered_at    │
                       └────────────────┘     └────────────────┘

┌────────────────┐     ┌────────────────┐
│PracticeQuestion│────▶│ PracticeChoice │
├────────────────┤ 1:N ├────────────────┤
│ id             │     │ id             │
│ question_text  │     │ question_id    │
│ explanation    │     │ choice_text    │
│ genre          │     │ is_correct     │
│ difficulty     │     └────────────────┘
└────────────────┘
```

---

## API/ルート構成

### 管理者ルート (`/admin/*`)

| メソッド | パス | コントローラー | 機能 |
|---------|------|---------------|------|
| GET | /admin/dashboard | DashboardController@index | ダッシュボード |
| GET/POST | /admin/events | EventController@index/store | イベント一覧/作成 |
| GET/PUT/DELETE | /admin/events/{id} | EventController@show/update/destroy | イベント詳細/更新/削除 |
| GET/POST | /admin/questions | QuestionController@index/store | 問題一覧/作成 |
| GET | /admin/results | ResultController@index | 成績一覧 |
| GET | /admin/results/event/{id} | ResultController@eventResults | イベント別成績 |
| GET | /admin/statistics | StatisticsController@index | 統計・グラフ |
| GET/POST | /admin/accounts | AccountController@index/store | アカウント管理 |

### ユーザールート (`/user/*`)

| メソッド | パス | コントローラー | 機能 |
|---------|------|---------------|------|
| GET | /user/dashboard | DashboardController@index | ダッシュボード |
| GET | /user/exam/{event} | ExamController@show | 試験開始 |
| POST | /user/exam/{event}/answer | ExamController@answer | 回答送信 |
| GET | /user/my-results | ResultController@index | 自分の成績 |

### ゲストルート (`/*`)

| メソッド | パス | コントローラー | 機能 |
|---------|------|---------------|------|
| GET | / | HomeController@index | トップページ |
| POST | /guest/entry | GuestExamController@entry | ゲスト試験開始 |
| GET | /practice | PracticeController@index | 練習問題一覧 |
| GET | /practice/{id} | PracticeController@show | 練習問題実施 |
