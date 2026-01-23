# システム構成図 - Part 5: 管理者API・デプロイメント

## 🔧 管理者 API エンドポイント

### ユーザー管理

```
┌────────────────────────────────────────────────────────────────┐
│ 1. ユーザー一覧取得                                             │
├────────────────────────────────────────────────────────────────┤
│ GET /api/admin/users                                           │
│ Middleware: auth:admin                                         │
│                                                                │
│ クエリパラメータ:                                               │
│ ?page=1&per_page=20&search=山田&status=active&sort=-created_at │
│                                                                │
│ レスポンス: 200 OK                                             │
│ {                                                              │
│   "status": "success",                                         │
│   "data": {                                                    │
│     "users": [                                                 │
│       {                                                        │
│         "id": 1,                                               │
│         "student_id": "STU001",                                │
│         "email": "user@example.com",                           │
│         "full_name": "山田太郎",                              │
│         "is_active": true,                                     │
│         "last_login_at": "2026-01-23T09:30:00Z",              │
│         "total_exams": 3,                                      │
│         "avg_score": 78.5,                                     │
│         "created_at": "2026-01-01T10:00:00Z"                   │
│       },                                                       │
│       ...                                                      │
│     ],                                                         │
│     "pagination": {                                            │
│       "total": 342,                                            │
│       "current_page": 1,                                       │
│       "total_pages": 18,                                       │
│       "per_page": 20                                           │
│     }                                                          │
│   }                                                            │
│ }                                                              │
│                                                                │
│ エラー: 403 Forbidden / 401 Unauthorized                       │
└────────────────────────────────────────────────────────────────┘


┌────────────────────────────────────────────────────────────────┐
│ 2. ユーザー詳細情報取得                                         │
├────────────────────────────────────────────────────────────────┤
│ GET /api/admin/users/:id                                       │
│ Middleware: auth:admin                                         │
│                                                                │
│ レスポンス: 200 OK                                             │
│ {                                                              │
│   "status": "success",                                         │
│   "data": {                                                    │
│     "id": 1,                                                   │
│     "student_id": "STU001",                                    │
│     "email": "user@example.com",                               │
│     "full_name": "山田太郎",                                  │
│     "phone": "090-1234-5678",                                  │
│     "is_active": true,                                         │
│     "created_at": "2026-01-01T10:00:00Z",                      │
│     "last_login_at": "2026-01-23T09:30:00Z",                   │
│     "exam_history": [                                          │
│       {                                                        │
│         "exam_session_id": 42,                                 │
│         "event_id": 5,                                         │
│         "event_title": "プログラマー適性試験 Part1",          │
│         "final_score": 82,                                     │
│         "status": "completed",                                 │
│         "completed_at": "2026-01-22T10:45:00Z",               │
│         "violations": []                                       │
│       },                                                       │
│       ...                                                      │
│     ]                                                          │
│   }                                                            │
│ }                                                              │
│                                                                │
│ エラー: 404 Not Found                                          │
└────────────────────────────────────────────────────────────────┘


┌────────────────────────────────────────────────────────────────┐
│ 3. ユーザーアクティベーション/デアクティベーション              │
├────────────────────────────────────────────────────────────────┤
│ PATCH /api/admin/users/:id/status                              │
│ Middleware: auth:admin                                         │
│                                                                │
│ リクエスト:                                                     │
│ {                                                              │
│   "is_active": false,                                          │
│   "reason": "多数の不正行為検出"                              │
│ }                                                              │
│                                                                │
│ レスポンス: 200 OK                                             │
│ {                                                              │
│   "status": "success",                                         │
│   "message": "ユーザー STU001 をアクティベーション解除しました" │
│ }                                                              │
│                                                                │
│ 監査ログ: audit_logs テーブルに記録                             │
│   action: 'deactivate'                                        │
│   resource_type: 'user'                                       │
│   resource_id: 1                                              │
│   changes: {is_active: false}                                 │
└────────────────────────────────────────────────────────────────┘
```

### イベント・試験管理

```
┌────────────────────────────────────────────────────────────────┐
│ 4. イベント作成                                                 │
├────────────────────────────────────────────────────────────────┤
│ POST /api/admin/events                                         │
│ Middleware: auth:admin                                         │
│                                                                │
│ リクエスト:                                                     │
│ {                                                              │
│   "title": "プログラマー適性試験 Part2",                      │
│   "description": "2025年1月実施版",                           │
│   "start_at": "2026-02-01T10:00:00Z",                          │
│   "end_at": "2026-02-01T18:00:00Z",                            │
│   "duration_minutes": 120,                                     │
│   "total_questions": 150,                                      │
│   "passing_score": 60,                                         │
│   "difficulty": "hard",                                        │
│   "questions_config": {                                        │
│     "category_distribution": {                                 │
│       "logic": 30,                                             │
│       "programming": 60,                                       │
│       "database": 40,                                          │
│       "security": 20                                           │
│     }                                                          │
│   }                                                            │
│ }                                                              │
│                                                                │
│ レスポンス: 201 Created                                        │
│ {                                                              │
│   "status": "success",                                         │
│   "data": {                                                    │
│     "event_id": 6,                                             │
│     "session_code": "EXAM2025002",                             │
│     "title": "プログラマー適性試験 Part2",                    │
│     "created_at": "2026-01-23T10:00:00Z"                       │
│   }                                                            │
│ }                                                              │
│                                                                │
│ エラー: 422 Validation Error                                   │
└────────────────────────────────────────────────────────────────┘


┌────────────────────────────────────────────────────────────────┐
│ 5. 成績レポート取得                                             │
├────────────────────────────────────────────────────────────────┤
│ GET /api/admin/reports/event/:id                               │
│ Middleware: auth:admin                                         │
│                                                                │
│ クエリパラメータ:                                               │
│ ?format=json&include=violations&export=csv                     │
│                                                                │
│ レスポンス: 200 OK / CSV ダウンロード                           │
│ {                                                              │
│   "status": "success",                                         │
│   "data": {                                                    │
│     "event_id": 5,                                             │
│     "event_title": "プログラマー適性試験 Part1",              │
│     "total_participants": 342,                                 │
│     "summary": {                                               │
│       "avg_score": 72.4,                                       │
│       "median_score": 75,                                      │
│       "min_score": 15,                                         │
│       "max_score": 100,                                        │
│       "pass_rate": 0.78,                                       │
│       "violations_count": 12                                   │
│     },                                                         │
│     "grade_distribution": {                                    │
│       "A+": 45,                                                │
│       "A": 123,                                                │
│       "B": 134,                                                │
│       "C": 35,                                                 │
│       "F": 5                                                   │
│     },                                                         │
│     "results": [                                               │
│       {                                                        │
│         "user_id": 1,                                          │
│         "student_id": "STU001",                                │
│         "full_name": "山田太郎",                              │
│         "final_score": 82,                                     │
│         "grade": "A",                                          │
│         "violations": [                                        │
│           {                                                    │
│             "type": "timing",                                  │
│             "severity": "low",                                 │
│             "description": "問題1で異常なペース検出"         │
│           }                                                    │
│         ],                                                     │
│         "completed_at": "2026-01-22T10:45:00Z"                │
│       },                                                       │
│       ...                                                      │
│     ]                                                          │
│   }                                                            │
│ }                                                              │
│                                                                │
│ エラー: 404 Not Found / 403 Forbidden                          │
└────────────────────────────────────────────────────────────────┘


┌────────────────────────────────────────────────────────────────┐
│ 6. 不正行為ユーザー調査                                         │
├────────────────────────────────────────────────────────────────┤
│ GET /api/admin/violations/flagged                              │
│ Middleware: auth:admin                                         │
│                                                                │
│ クエリパラメータ:                                               │
│ ?severity=high&reviewed=false&sort=-detected_at                │
│                                                                │
│ レスポンス: 200 OK                                             │
│ {                                                              │
│   "status": "success",                                         │
│   "data": {                                                    │
│     "violations": [                                            │
│       {                                                        │
│         "violation_id": 1,                                     │
│         "exam_session_id": 42,                                 │
│         "user_id": 5,                                          │
│         "student_id": "STU005",                                │
│         "event_id": 5,                                         │
│         "event_title": "プログラマー適性試験 Part1",          │
│         "violation_type": "ip_change",                         │
│         "severity": "high",                                    │
│         "description": "試験中盤でIPアドレスが変更",          │
│         "initial_ip": "192.168.1.100",                         │
│         "final_ip": "203.0.113.50",                            │
│         "detected_at": "2026-01-22T10:25:00Z",                │
│         "exam_session": {                                      │
│           "score": 95,                                         │
│           "status": "flagged",                                 │
│           "completed_at": "2026-01-22T10:45:00Z"              │
│         },                                                     │
│         "actions": [                                           │
│           "review",                                            │
│           "invalidate_score",                                  │
│           "disable_user"                                       │
│         ]                                                      │
│       },                                                       │
│       ...                                                      │
│     ]                                                          │
│   }                                                            │
│ }                                                              │
│                                                                │
│ エラー: 401 Unauthorized / 403 Forbidden                       │
└────────────────────────────────────────────────────────────────┘
```

---

## 🚀 デプロイメント・本番環境構成

### Docker Compose 構成図

```
┌──────────────────────────────────────────────────────────────────┐
│ docker-compose.yml                                               │
│ (サービス・ネットワーク・ボリューム定義)                          │
│                                                                  │
│ Version: 3.8                                                     │
│                                                                  │
│ Services:                                                        │
│ ├─ laravel.test (PHP-FPM + Laravel)                             │
│ │  ├─ Image: sail-8.4/app:latest                               │
│ │  ├─ Build: ./vendor/laravel/sail/runtimes/8.4/Dockerfile   │
│ │  ├─ Ports: 0.0.0.0:80->80, 0.0.0.0:5173->5173              │
│ │  ├─ Environment: LARAVEL_SAIL=1, XDEBUG_MODE=off             │
│ │  ├─ Volumes:                                                 │
│ │  │  ├─ .:/var/www/html                                       │
│ │  │  └─ /var/www/html/node_modules (バインドマウント)          │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ ├─ mysql (MySQL 8.0 Database Server)                           │
│ │  ├─ Image: mysql/mysql-server:8.0                            │
│ │  ├─ Ports: 0.0.0.0:3306->3306                               │
│ │  ├─ Environment:                                             │
│ │  │  ├─ MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}                  │
│ │  │  ├─ MYSQL_DATABASE: ${DB_DATABASE}                       │
│ │  │  ├─ MYSQL_USER: ${DB_USERNAME}                           │
│ │  │  └─ MYSQL_PASSWORD: ${DB_PASSWORD}                       │
│ │  ├─ Volumes:                                                 │
│ │  │  ├─ sail-mysql:/var/lib/mysql (永続化)                    │
│ │  │  └─ init-db.sql:/docker-entrypoint-initdb.d/             │
│ │  ├─ Healthcheck: mysqladmin ping                             │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ ├─ redis (Redis Cache & Session Store)                         │
│ │  ├─ Image: redis:alpine                                      │
│ │  ├─ Ports: 0.0.0.0:6379->6379                               │
│ │  ├─ Command: redis-server --appendonly yes                   │
│ │  ├─ Volumes:                                                 │
│ │  │  └─ sail-redis:/data (永続化)                             │
│ │  ├─ Healthcheck: redis-cli ping                              │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ ├─ meilisearch (フルテキスト検索)                              │
│ │  ├─ Image: getmeili/meilisearch:latest                       │
│ │  ├─ Ports: 0.0.0.0:7700->7700                               │
│ │  ├─ Environment: MEILI_NO_ANALYTICS=true                     │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ ├─ mailpit (メール テスト)                                      │
│ │  ├─ Image: axllent/mailpit:latest                            │
│ │  ├─ Ports: 0.0.0.0:1025->1025, 0.0.0.0:8025->8025          │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ ├─ selenium (E2E テスト用ブラウザ)                             │
│ │  ├─ Image: selenium/standalone-chromium:latest               │
│ │  ├─ Shm_size: 2gb (Chrome メモリ)                            │
│ │  ├─ Ports: 4444, 5900, 9000                                 │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ ├─ phpmyadmin (DB 管理)                                         │
│ │  ├─ Image: phpmyadmin/phpmyadmin                             │
│ │  ├─ Ports: 0.0.0.0:8080->80                                 │
│ │  ├─ Environment:                                             │
│ │  │  ├─ PMA_HOST: mysql                                       │
│ │  │  ├─ PMA_USER: ${DB_USERNAME}                              │
│ │  │  └─ PMA_PASSWORD: ${DB_PASSWORD}                          │
│ │  └─ Networks: sail                                           │
│ │                                                              │
│ └─ nginx (リバースプロキシ - オプション)                        │
│    ├─ Image: nginx:alpine                                      │
│    ├─ Ports: 0.0.0.0:80->80, 0.0.0.0:443->443                │
│    ├─ Volumes:                                                 │
│    │  ├─ ./docker/nginx/default.conf:/etc/nginx/conf.d/      │
│    │  ├─ ./docker/nginx/ssl/:/etc/nginx/ssl/                  │
│    │  └─ ./public:/var/www/html/public                        │
│    └─ Networks: sail                                           │
│                                                                  │
│ Networks:                                                        │
│ ├─ sail (custom bridge network)                                │
│ │  ├─ driver: bridge                                           │
│ │  └─ driver_opts:                                             │
│ │     └─ com.docker.network.driver.mtu: 1450                   │
│ │                                                              │
│ Volumes:                                                        │
│ ├─ sail-mysql                                                  │
│ ├─ sail-redis                                                  │
│ └─ sail-meilisearch                                            │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

### 本番環境デプロイメント戦略

```
┌──────────────────────────────────────────────────────────────────┐
│ 本番環境 (AWS / Azure / 自社サーバー)                            │
│                                                                  │
│ インフラ層:                                                      │
│ ├─ Load Balancer                                                │
│ │  ├─ SSL/TLS ターミネーション                                  │
│ │  ├─ ヘルスチェック                                            │
│ │  └─ トラフィック分散                                         │
│ │                                                              │
│ ├─ Multiple App Servers (Docker Swarm / Kubernetes)            │
│ │  ├─ Node 1: laravel.test (レプリカ)                         │
│ │  ├─ Node 2: laravel.test (レプリカ)                         │
│ │  └─ Node 3: laravel.test (レプリカ)                         │
│ │                                                              │
│ ├─ Managed Database (RDS / Azure Database)                      │
│ │  ├─ MySQL 8.0 with High Availability                         │
│ │  ├─ Multi-AZ / リージョンレプリケーション                     │
│ │  ├─ 自動バックアップ (35日保持)                              │
│ │  └─ 読み取り専用レプリカ (レポート用)                         │
│ │                                                              │
│ ├─ Redis Cluster (ElastiCache / Azure Cache)                    │
│ │  ├─ セッションストア (高可用性)                               │
│ │  ├─ キャッシュ層 (自動フェイルオーバー)                       │
│ │  └─ Pub/Sub (リアルタイム通知)                               │
│ │                                                              │
│ ├─ Blob Storage (S3 / Azure Blob)                               │
│ │  ├─ 証明書・レポートファイル保存                             │
│ │  ├─ バージョニング有効                                       │
│ │  └─ CloudFront / CDN キャッシュ                               │
│ │                                                              │
│ ├─ Log Aggregation (CloudWatch / Log Analytics)                 │
│ │  ├─ アプリケーションログ集約                                 │
│ │  ├─ リアルタイムアラート                                     │
│ │  └─ ダッシュボード可視化                                     │
│ │                                                              │
│ └─ Monitoring & Alerting (DataDog / New Relic)                  │
│    ├─ APM (Application Performance Monitoring)                  │
│    ├─ Infrastructure Metrics                                   │
│    └─ メール/Slack アラート                                     │
│                                                                  │
│ CI/CD Pipeline:                                                 │
│ ├─ GitHub Actions / GitLab CI                                  │
│ ├─ Test: PHPUnit / Playwright                                  │
│ ├─ Build: Docker イメージ生成                                   │
│ ├─ Registry: Docker Hub / ECR                                  │
│ ├─ Deploy: Blue/Green デプロイメント                            │
│ └─ Rollback: 自動ロールバック (健康チェック失敗時)              │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## ✅ まとめ

複数ファイルに分割して、以下のシステム構成図を作成しました：

| ファイル | 内容 |
|---------|------|
| `SYSTEM_DIAGRAM_01_MODULES.md` | モジュール階層構成 |
| `SYSTEM_DIAGRAM_02_INFRASTRUCTURE.md` | インフラストラクチャ・デプロイ構成 |
| `SYSTEM_DIAGRAM_03_DATAFLOW.md` | ユースケースのデータフロー |
| `SYSTEM_DIAGRAM_04_SCORING_SECURITY.md` | 成績計算・7層セキュリティ |
| `SYSTEM_DIAGRAM_05_DB_API.md` | DB スキーマ・API仕様 |
| `SYSTEM_DIAGRAM_06_ADMIN_DEPLOYMENT.md` | 管理者API・デプロイメント戦略 |

すべての構成図は **VS Code** で見やすいテキスト形式、または **draw.io** などで図形化可能です！
