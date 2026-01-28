# コンテナ化・カプセル化ガイド

## 1. コンテナ化（Docker）

### 1.1 コンテナ化とは

アプリケーションとその依存関係を「コンテナ」という独立した単位にパッケージ化する技術。

**メリット:**

- 環境の一貫性（開発・本番で同じ環境）
- 移植性（どのサーバーでも同じように動作）
- 分離性（他のアプリケーションに影響しない）
- スケーラビリティ（簡単に複製可能）

### 1.2 本プロジェクトのDockerfile

```dockerfile
# ベースイメージ
FROM php:8.2-fpm

# 依存パッケージのインストール
RUN apt-get update && apt-get install -y \
    nginx \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql

# アプリケーションコードをコピー
COPY . /var/www/html

# Composerパッケージインストール
RUN composer install --no-dev

# フロントエンドビルド
RUN npm install && npm run build

# エントリーポイント
CMD ["php-fpm"]
```

### 1.3 Docker Compose構成

```yaml
services:
    # アプリケーションコンテナ
    app:
        build: .
        depends_on:
            - db
            - redis

    # データベースコンテナ
    db:
        image: mysql:8.0
        volumes:
            - db_data:/var/lib/mysql

    # キャッシュコンテナ
    redis:
        image: redis:7.0

volumes:
    db_data:
```

### 1.4 コンテナ間通信

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│     App     │────▶│     DB      │     │    Redis    │
│  Container  │     │  Container  │     │  Container  │
│  (Laravel)  │────────────────────────▶│  (Cache)    │
└─────────────┘     └─────────────┘     └─────────────┘
      │
      ▼
  Port 80 (公開)
```

---

## 2. カプセル化（オブジェクト指向設計）

### 2.1 カプセル化とは

データとそれを操作するメソッドを一つの単位（クラス）にまとめ、外部からの直接アクセスを制限する設計原則。

**メリット:**

- データの保護（不正な変更を防ぐ）
- 実装の隠蔽（内部実装を変更しても外部に影響しない）
- コードの再利用性向上

### 2.2 本プロジェクトでの実装例

#### サービスクラスによるカプセル化

```php
// app/Services/ExamService.php
class ExamService
{
    // プライベートプロパティ（外部から直接アクセス不可）
    private $cache;

    // パブリックメソッド（外部に公開するインターフェース）
    public function calculateRank(float $score, int $maxScore): array
    {
        // 内部ロジックは隠蔽
        $scaleFactor = $maxScore / 95;

        if ($score >= 61 * $scaleFactor) {
            return ['rank' => 'A', 'rankName' => 'Platinum'];
        }
        // ...
    }

    // プライベートメソッド（内部でのみ使用）
    private function validateScore(float $score): bool
    {
        return $score >= 0;
    }
}
```

#### コントローラーでの使用

```php
// app/Http/Controllers/ExamResultController.php
class ExamResultController extends Controller
{
    // 依存性注入（DI）
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    public function showResult($sessionUuid)
    {
        // サービスの公開メソッドのみ使用
        // 内部実装の詳細は知る必要がない
        $rankInfo = $this->examService->calculateRank($score, $maxScore);
    }
}
```

### 2.3 レイヤー分離

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                   │
│  Controllers: リクエスト受付、レスポンス返却            │
│  ・ExamController                                       │
│  ・ExamAnswerController                                 │
│  ・ExamResultController                                 │
└────────────────────────┬────────────────────────────────┘
                         │ 依存
                         ▼
┌─────────────────────────────────────────────────────────┐
│                    Business Layer                       │
│  Services: ビジネスロジックのカプセル化                 │
│  ・ExamService                                          │
│    - calculateRank()                                    │
│    - sanitizeAnswers()                                  │
│    - getQuestionCountByEvent()                         │
└────────────────────────┬────────────────────────────────┘
                         │ 依存
                         ▼
┌─────────────────────────────────────────────────────────┐
│                    Data Layer                           │
│  Models: データアクセスのカプセル化                     │
│  ・ExamSession                                          │
│  ・Question                                             │
│  ・Answer                                               │
└─────────────────────────────────────────────────────────┘
```

---

## 3. 実践的なカプセル化パターン

### 3.1 リポジトリパターン

```php
// データベースアクセスをカプセル化
interface ExamSessionRepositoryInterface
{
    public function findByUser(int $userId): ?ExamSession;
    public function create(array $data): ExamSession;
}

class ExamSessionRepository implements ExamSessionRepositoryInterface
{
    public function findByUser(int $userId): ?ExamSession
    {
        return ExamSession::where('user_id', $userId)
            ->whereNull('finished_at')
            ->first();
    }
}
```

### 3.2 ファクトリーパターン

```php
// オブジェクト生成をカプセル化
class ExamSessionFactory
{
    public static function create(User $user, Event $event): ExamSession
    {
        return ExamSession::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'started_at' => now(),
            'current_part' => 1,
            'security_log' => json_encode([
                'exam_type' => $event->exam_type,
            ]),
        ]);
    }
}
```

### 3.3 値オブジェクト

```php
// 不変のデータをカプセル化
class ExamRank
{
    private string $rank;
    private string $rankName;

    public function __construct(string $rank, string $rankName)
    {
        $this->rank = $rank;
        $this->rankName = $rankName;
    }

    public function getRank(): string { return $this->rank; }
    public function getRankName(): string { return $this->rankName; }
}
```

---

## 4. 本プロジェクトの設計方針

### 4.1 コントローラーの分割

大きなコントローラーを責務ごとに分割:

| コントローラー       | 責務             |
| -------------------- | ---------------- |
| ExamController       | 試験の開始・進行 |
| ExamAnswerController | 解答の保存       |
| ExamResultController | 結果の表示       |
| GuestExamController  | ゲスト用機能     |

### 4.2 サービスクラスの活用

共通ロジックをサービスに集約:

```php
// ExamService.php
- calculateRank()      // ランク計算
- sanitizeAnswers()    // 回答データのサニタイズ
- getEventBySessionCode()  // イベント取得
- getPartTimeLimitByEvent()  // 時間制限取得
```

### 4.3 利点

1. **テスタビリティ**: 各クラスを独立してテスト可能
2. **保守性**: 変更が局所化される
3. **再利用性**: 同じロジックを複数箇所で使用
4. **可読性**: 責務が明確で理解しやすい
