# モジュール構成図

## 1. アプリケーション全体構成

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    プログラマ適性検査システム                                   │
│                    (Programmer Aptitude Test System)                         │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                         フロントエンド層                                 │  │
│  │                      (Vue 3 + Inertia.js)                              │  │
│  │  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────────────┐  │  │
│  │  │   認証系ページ   │ │  受験者系ページ  │ │     管理者系ページ       │  │  │
│  │  │  ・Login        │ │  ・Welcome      │ │  ・Dashboard            │  │  │
│  │  │  ・Register     │ │  ・Practice     │ │  ・Events (CRUD)        │  │  │
│  │  │                 │ │  ・Part         │ │  ・Questions (CRUD)     │  │  │
│  │  │                 │ │  ・Result       │ │  ・Users (CRUD)         │  │  │
│  │  │                 │ │  ・Certificate  │ │  ・Results/Statistics   │  │  │
│  │  └─────────────────┘ └─────────────────┘ └─────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                        │                                     │
│                                        ▼                                     │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                         コントローラー層                                 │  │
│  │                      (Laravel Controllers)                             │  │
│  │  ┌─────────────────────────────────────────────────────────────────┐  │  │
│  │  │                        認証系                                    │  │  │
│  │  │  ・SessionCodeController  ・AuthenticatedSessionController      │  │  │
│  │  │  ・RegisteredUserController                                     │  │  │
│  │  └─────────────────────────────────────────────────────────────────┘  │  │
│  │  ┌─────────────────────────────────────────────────────────────────┐  │  │
│  │  │                       受験者系                                   │  │  │
│  │  │  ・ExamController        ・PracticeController                   │  │  │
│  │  │  ・ProfileController                                            │  │  │
│  │  └─────────────────────────────────────────────────────────────────┘  │  │
│  │  ┌─────────────────────────────────────────────────────────────────┐  │  │
│  │  │                       管理者系                                   │  │  │
│  │  │  ・AdminAuthController            ・EventManagementController   │  │  │
│  │  │  ・QuestionManagementController   ・UserManagementController    │  │  │
│  │  │  ・ResultsManagementController    ・ResultsComlinkController    │  │  │
│  │  └─────────────────────────────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                        │                                     │
│                                        ▼                                     │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                          モデル層                                       │  │
│  │                      (Eloquent Models)                                 │  │
│  │                                                                        │  │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐  │  │
│  │  │    User      │ │    Admin     │ │    Event     │ │   Question   │  │  │
│  │  │  (受験者)    │ │  (管理者)    │ │  (イベント)  │ │    (問題)    │  │  │
│  │  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘  │  │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐  │  │
│  │  │   Choice     │ │   Answer     │ │ ExamSession  │ │ExamViolation │  │  │
│  │  │   (選択肢)   │ │   (回答)     │ │ (試験セッション)│ │ (不正行為)  │  │  │
│  │  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘  │  │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐                   │  │
│  │  │EventQuestion │ │PracticeQuestion│ │PracticeChoice│                   │  │
│  │  │(イベント問題)│ │ (練習問題)    │ │(練習選択肢)  │                   │  │
│  │  └──────────────┘ └──────────────┘ └──────────────┘                   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                        │                                     │
│                                        ▼                                     │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                         データベース層                                   │  │
│  │                        (MySQL 8.0)                                     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

## 2. データベース構成図 (ER図)

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│     users       │      │     admins      │      │     events      │
├─────────────────┤      ├─────────────────┤      ├─────────────────┤
│ id              │      │ id              │      │ id              │
│ name            │      │ name            │      │ name            │
│ email           │      │ email           │      │ description     │
│ password        │      │ password        │      │ passphrase      │
│ created_at      │      │ created_at      │      │ max_users       │
│ updated_at      │      │ updated_at      │      │ starts_at       │
└────────┬────────┘      └─────────────────┘      │ ends_at         │
         │                                        │ status          │
         │                                        │ created_at      │
         │                                        │ updated_at      │
         │                                        └────────┬────────┘
         │                                                 │
         │                 ┌───────────────────────────────┤
         │                 │                               │
         ▼                 ▼                               ▼
┌─────────────────┐ ┌─────────────────┐      ┌─────────────────┐
│  exam_sessions  │ │ event_questions │      │   questions     │
├─────────────────┤ ├─────────────────┤      ├─────────────────┤
│ id              │ │ id              │      │ id              │
│ user_id (FK)    │ │ event_id (FK)   │      │ part            │
│ event_id (FK)   │ │ question_id (FK)│←─────│ number          │
│ uuid            │ │ display_order   │      │ content         │
│ part1_score     │ │ created_at      │      │ correct_answer  │
│ part2_score     │ │ updated_at      │      │ explanation     │
│ part3_score     │ └─────────────────┘      │ created_at      │
│ total_score     │                          │ updated_at      │
│ started_at      │                          └────────┬────────┘
│ finished_at     │                                   │
│ status          │                                   │
│ created_at      │                                   │
│ updated_at      │                                   ▼
└────────┬────────┘                          ┌─────────────────┐
         │                                   │    choices      │
         │                                   ├─────────────────┤
         │                                   │ id              │
         ▼                                   │ question_id(FK) │
┌─────────────────┐                          │ label           │
│    answers      │                          │ content         │
├─────────────────┤                          │ is_correct      │
│ id              │                          │ created_at      │
│ exam_session_id │                          │ updated_at      │
│ question_id     │                          └─────────────────┘
│ selected_choice │
│ is_correct      │
│ answered_at     │
│ created_at      │
│ updated_at      │
└─────────────────┘

┌─────────────────┐
│ exam_violations │
├─────────────────┤
│ id              │
│ exam_session_id │
│ violation_type  │
│ description     │
│ occurred_at     │
│ created_at      │
│ updated_at      │
└─────────────────┘
```

## 3. ファイル構成

```
ProgrammerAptitudeTest/
├── app/                          # アプリケーションコア
│   ├── Console/                  # Artisanコマンド
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # 管理者用コントローラー
│   │   │   │   ├── AdminAuthController.php
│   │   │   │   ├── EventManagementController.php
│   │   │   │   └── ResultsComlinkController.php
│   │   │   ├── Auth/             # 認証コントローラー
│   │   │   ├── ExamController.php
│   │   │   ├── PracticeController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── QuestionManagementController.php
│   │   │   ├── ResultsManagementController.php
│   │   │   ├── SessionCodeController.php
│   │   │   └── UserManagementController.php
│   │   └── Middleware/           # ミドルウェア
│   ├── Models/                   # Eloquentモデル
│   │   ├── Admin.php
│   │   ├── Answer.php
│   │   ├── Choice.php
│   │   ├── Event.php
│   │   ├── EventQuestion.php
│   │   ├── ExamSession.php
│   │   ├── ExamViolation.php
│   │   ├── PracticeChoice.php
│   │   ├── PracticeQuestion.php
│   │   ├── Question.php
│   │   └── User.php
│   └── Providers/                # サービスプロバイダー
├── config/                       # 設定ファイル
├── database/
│   ├── migrations/               # マイグレーション
│   └── seeders/                  # シーダー
├── resources/
│   ├── css/                      # スタイルシート
│   └── js/
│       ├── Components/           # Vueコンポーネント
│       ├── Layouts/              # レイアウト
│       └── Pages/                # ページコンポーネント
│           ├── Admin/            # 管理者画面
│           ├── Auth/             # 認証画面
│           ├── exam/             # 試験画面
│           ├── Practice/         # 練習画面
│           └── Profile/          # プロフィール画面
├── routes/
│   ├── web.php                   # Webルート
│   ├── api.php                   # APIルート
│   └── admin.php                 # 管理者ルート
├── docker/                       # Docker設定
├── docs/                         # ドキュメント
│   └── diagrams/                 # 図表
└── docker-compose.prod-test.yml  # 本番用Docker Compose
```

## 4. 主要機能モジュール

### 4.1 認証モジュール
| ファイル | 機能 |
|---------|------|
| SessionCodeController | セッションコード認証（ゲスト用） |
| AdminAuthController | 管理者認証 |
| RegisteredUserController | ユーザー登録 |
| AuthenticatedSessionController | ログイン/ログアウト |

### 4.2 試験モジュール
| ファイル | 機能 |
|---------|------|
| ExamController | 試験の開始・進行・終了管理 |
| PracticeController | 練習モード管理 |
| Part.vue | 試験問題表示（第1〜3部） |
| Practice.vue | 練習問題表示 |

### 4.3 管理モジュール
| ファイル | 機能 |
|---------|------|
| EventManagementController | イベント（試験会）管理 |
| QuestionManagementController | 問題管理 |
| UserManagementController | ユーザー管理 |
| ResultsManagementController | 結果集計・統計 |

### 4.4 結果・統計モジュール
| ファイル | 機能 |
|---------|------|
| Result.vue | 受験者向け結果表示 |
| Statistics.vue | 管理者向け統計 |
| Certificate.vue | 証明書表示 |
| ResultsComlinkController | COMLINK連携 |
