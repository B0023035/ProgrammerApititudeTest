# プログラマー適性検査システム - ドキュメント索引

## 📚 ドキュメント一覧

このプロジェクトの詳細な構成、設計、実装ガイドは以下のドキュメントを参照してください。

### 1. **PROJECT_STRUCTURE.md** - プロジェクト全体構成

- **内容**: プロジェクト概要、ディレクトリ構成、主要機能、パフォーマンス考慮事項
- **対象**: 新しくプロジェクトに参加するエンジニア
- **主要セクション**:
    - 📁 ディレクトリ構成
    - 🔄 リクエスト/レスポンスフロー
    - 🔌 CSRF トークン管理
    - 🛡️ セキュリティ機能
    - 🚀 デプロイメント
    - 🔧 環境変数
    - 🐛 トラブルシューティング

**使用シーン**:

```
- 「このプロジェクトの構成を知りたい」
- 「セッション管理がどうなっているか?」
- 「CSRF トークンの流れを理解したい」
- 「どのコントローラーがどのファイルにあるか?」
```

---

### 2. **MODEL_RELATIONSHIPS.md** - モデル関係図・データスキーマ

- **内容**: データベースモデル詳細、リレーション図、ER 図、使用シーン別クエリ例
- **対象**: バックエンド開発者、データベース設計者
- **主要セクション**:
    - 🔗 モデル関係図 (テキスト ER 図)
    - 📋 全 10 モデルの詳細仕様
    - 🔄 リレーション検索パターン集
    - 📊 データモデル使用シーン

**モデル一覧**:

1. User - 一般ユーザー
2. Admin - 管理者ユーザー
3. ExamSession - 試験セッション
4. Event - 試験イベント
5. Question - 本番試験問題
6. Choice - 本番問題選択肢
7. Answer - ユーザー解答
8. PracticeQuestion - 練習問題
9. PracticeChoice - 練習問題選択肢
10. ExamViolation - 不正検知記録

**使用シーン**:

```
- 「User と ExamSession の関係は?」
- 「特定のセッションの全解答を取得したい」
- 「パート別の正答率を計算したい」
- 「新しいマイグレーションを作成したい」
```

---

### 3. **API_ROUTES.md** - ルート定義・コントローラー仕様

- **内容**: 全ルート定義、コントローラーメソッド詳細、リクエスト/レスポンス例
- **対象**: フロントエンド開発者、API 利用者、テスター
- **主要セクション**:
    - 🛣️ ルート構成 (Web, Auth, Admin)
    - 🎮 コントローラー詳細
    - 🔐 ミドルウェア一覧
    - 📊 リクエスト・レスポンス例
    - 🔄 HTTP ステータスコード

**ルートグループ**:

- 認証なし (ゲストアクセス可)
- 認証が必要 (ユーザー)
- 管理者専用

**使用シーン**:

```
- 「/exam/part/1 エンドポイントのレスポンス形式は?」
- 「POST /exam/part/1/answer でどんなデータを送る?」
- 「ExamController の start() メソッドは何をしているか?」
- 「API テストを作成したい」
```

---

### 4. **AUTHENTICATION_GUIDE.md** - 認証フロー・開発ガイド

- **内容**: 認証フロー詳細、CSRF 保護、ゲスト認証、管理者認証、開発ガイドライン
- **対象**: 認証機能開発者、セキュリティ担当者
- **主要セクション**:
    - 🔐 認証システム詳細
    - 👥 一般ユーザー認証フロー
    - 🔑 ログインプロセス詳細
    - 🛡️ CSRF トークン保護メカニズム
    - 👤 ゲストユーザーフロー
    - 🔑 管理者認証フロー
    - 🛠️ 開発ガイドライン
    - 📝 テストの書き方

**使用シーン**:

```
- 「ログインフローの全体図が見たい」
- 「新しい認証ガードを追加したい」
- 「ミドルウェアをカスタマイズしたい」
- 「認証機能のテストを書きたい」
- 「CSRF トークンの流れを理解したい」
```

---

## 🎯 ドキュメント選択ガイド

### シーン別ガイド

#### 🚀 新規参加エンジニア

```
1. PROJECT_STRUCTURE.md       - 全体像把握
2. MODEL_RELATIONSHIPS.md      - データ構造理解
3. API_ROUTES.md              - 機能別ルート確認
4. AUTHENTICATION_GUIDE.md    - 認証機能理解
```

#### 🗄️ バックエンド開発

```
1. MODEL_RELATIONSHIPS.md      - モデル設計
2. AUTHENTICATION_GUIDE.md    - 認証ロジック
3. API_ROUTES.md              - コントローラー実装
4. PROJECT_STRUCTURE.md       - 全体構成確認
```

#### 🎨 フロントエンド開発

```
1. API_ROUTES.md              - API 仕様確認
2. PROJECT_STRUCTURE.md       - ファイル構成
3. AUTHENTICATION_GUIDE.md    - 認証フロー
4. MODEL_RELATIONSHIPS.md     - データ構造
```

#### 🔒 セキュリティレビュー

```
1. AUTHENTICATION_GUIDE.md    - 認証・認可
2. PROJECT_STRUCTURE.md       - CSRF 保護
3. MODEL_RELATIONSHIPS.md     - データ管理
4. API_ROUTES.md              - リクエスト検証
```

#### 📊 DBA・データ設計

```
1. MODEL_RELATIONSHIPS.md      - スキーマ詳細
2. PROJECT_STRUCTURE.md       - 環境変数・DB 設定
3. API_ROUTES.md              - クエリパターン
```

#### 🧪 テスト・QA

```
1. API_ROUTES.md              - API 仕様
2. AUTHENTICATION_GUIDE.md    - テスト例
3. PROJECT_STRUCTURE.md       - エラー処理
4. MODEL_RELATIONSHIPS.md     - データケース
```

---

## 🔍 クイック検索

### 機能別ドキュメント位置

| 機能                   | ドキュメント            | セクション                |
| ---------------------- | ----------------------- | ------------------------- |
| ユーザーログイン       | AUTHENTICATION_GUIDE.md | 👥 一般ユーザー認証フロー |
| 試験セッション管理     | MODEL_RELATIONSHIPS.md  | ExamSession モデル        |
| 問題・選択肢           | MODEL_RELATIONSHIPS.md  | Question/Choice モデル    |
| 解答記録               | MODEL_RELATIONSHIPS.md  | Answer モデル             |
| ルート定義             | API_ROUTES.md           | 🛣️ ルート構成             |
| コントローラー         | API_ROUTES.md           | 🎮 コントローラー詳細     |
| CSRF トークン          | PROJECT_STRUCTURE.md    | 🔌 CSRF トークン管理      |
| CSRF トークン (詳細)   | AUTHENTICATION_GUIDE.md | 🛡️ CSRF トークン保護      |
| ミドルウェア           | API_ROUTES.md           | 🔐 ミドルウェア一覧       |
| セキュリティ           | PROJECT_STRUCTURE.md    | 🛡️ セキュリティ機能       |
| デプロイ               | PROJECT_STRUCTURE.md    | 🚀 デプロイメント         |
| 環境設定               | PROJECT_STRUCTURE.md    | 🔧 環境変数               |
| トラブルシューティング | PROJECT_STRUCTURE.md    | 🐛 トラブルシューティング |

---

## 📖 ドキュメントの読み方

### 構造

各ドキュメントは以下の構造を持っています:

```
[タイトル]
  ├─ 概要・目的
  ├─ 【絵文字】主要セクション
  │  ├─ 詳細説明
  │  ├─ コード例
  │  └─ 表・図
  ├─ 【絵文字】次セクション
  │  └─ ...
  └─ 参考資料・リンク
```

### 記号の意味

| 記号 | 意味                     | 例                     |
| ---- | ------------------------ | ---------------------- |
| 🔗   | リレーション・つながり   | モデル関係             |
| 🔐   | セキュリティ             | 認証・CSRF             |
| 🔄   | フロー・処理順序         | ライフサイクル         |
| 📊   | データ・構造             | テーブル・スキーマ     |
| 🛣️   | ルート・パス             | URL パターン           |
| 🎮   | コントローラー・ロジック | 処理実装               |
| 🛠️   | ツール・開発             | 実装ガイド             |
| 📝   | ドキュメント・テキスト   | テスト・記述例         |
| 🐛   | バグ・エラー             | トラブルシューティング |

---

## 🔄 ドキュメント間の関連性

```
PROJECT_STRUCTURE.md (全体像)
    ├─ CSRF トークン管理
    │   └─ AUTHENTICATION_GUIDE.md (CSRF 詳細)
    ├─ セキュリティ機能
    │   └─ AUTHENTICATION_GUIDE.md (認証・認可)
    ├─ ディレクトリ構成
    │   └─ API_ROUTES.md (ルート定義)
    └─ パフォーマンス
        └─ MODEL_RELATIONSHIPS.md (クエリ最適化)

MODEL_RELATIONSHIPS.md (データ構造)
    ├─ モデル詳細
    │   └─ API_ROUTES.md (コントローラー実装)
    ├─ リレーション
    │   └─ API_ROUTES.md (リクエスト/レスポンス)
    └─ 検索パターン
        └─ API_ROUTES.md (コード例)

API_ROUTES.md (API 仕様)
    ├─ ミドルウェア
    │   └─ AUTHENTICATION_GUIDE.md (認証チェック)
    ├─ コントローラー
    │   └─ MODEL_RELATIONSHIPS.md (モデル操作)
    └─ レスポンス例
        └─ PROJECT_STRUCTURE.md (エラー処理)

AUTHENTICATION_GUIDE.md (認証フロー)
    ├─ CSRF トークン
    │   └─ PROJECT_STRUCTURE.md (全体流)
    ├─ ログインプロセス
    │   └─ API_ROUTES.md (POST /login)
    └─ テスト例
        └─ MODEL_RELATIONSHIPS.md (データ作成)
```

---

## 💡 使用例

### 例1: 「ユーザーが解答を送信するとき何が起こるか?」

**答え**: 複数のドキュメントを横断参照

```
1. API_ROUTES.md 参照
   └─ "POST /exam/part/{part}/answer" セクション
   └─ リクエストボディ・レスポンス形式確認

2. MODEL_RELATIONSHIPS.md 参照
   └─ "Answer モデル" セクション
   └─ 保存されるカラム・リレーション確認

3. AUTHENTICATION_GUIDE.md 参照
   └─ "CSRF トークン保護メカニズム" セクション
   └─ どうトークンが検証されるか確認

4. PROJECT_STRUCTURE.md 参照
   └─ "リクエスト/レスポンスフロー" セクション
   └─ ミドルウェアチェーン確認
```

---

### 例2: 「新しいミドルウェアを作成したい」

**答え**:

```
1. API_ROUTES.md 参照
   └─ "🔐 ミドルウェア一覧" セクション
   └─ 既存ミドルウェアの実装パターン

2. AUTHENTICATION_GUIDE.md 参照
   └─ "🛠️ 開発ガイドライン" > "3. ミドルウェアカスタム作成"
   └─ 新しいミドルウェアのテンプレート

3. PROJECT_STRUCTURE.md 参照
   └─ "ディレクトリ構成" - Middleware 位置確認
```

---

### 例3: 「パート別の正答率を計算したい」

**答え**:

```
1. MODEL_RELATIONSHIPS.md 参照
   └─ "5. リレーション検索パターン集" > "3. 問題別の正答率計算"
   └─ 類似のクエリ参照

2. 修正して実装
   └─ "パート別成績の集計" パターンも参照
```

---

## 📞 ドキュメント更新リクエスト

ドキュメントに不足・誤りがある場合:

1. **ドキュメント名** - 「PROJECT_STRUCTURE.md」
2. **セクション** - 「🚀 デプロイメント」
3. **問題の説明** - 「Docker 構成の例が古い」
4. **提案** - 「最新の docker-compose.yml 例に更新」

---

## 📋 ドキュメント管理

| ドキュメント            | 最終更新   | バージョン | 維持者             |
| ----------------------- | ---------- | ---------- | ------------------ |
| PROJECT_STRUCTURE.md    | 2025-12-09 | 1.0        | 開発チーム         |
| MODEL_RELATIONSHIPS.md  | 2025-12-09 | 1.0        | DB チーム          |
| API_ROUTES.md           | 2025-12-09 | 1.0        | API チーム         |
| AUTHENTICATION_GUIDE.md | 2025-12-09 | 1.0        | セキュリティチーム |

---

## 🎓 学習パス

### 初級 (1-2 時間)

```
1. PROJECT_STRUCTURE.md - "プロジェクト概要" セクション
2. MODEL_RELATIONSHIPS.md - "モデル詳細仕様" の User/ExamSession/Event
3. API_ROUTES.md - "Web ルート" セクション
```

### 中級 (3-4 時間)

```
1. 全ドキュメントのタイトル・セクション確認
2. AUTHENTICATION_GUIDE.md - 認証全体フロー
3. MODEL_RELATIONSHIPS.md - 全モデルのリレーション
4. API_ROUTES.md - コントローラーメソッド詳細
```

### 上級 (5+ 時間)

```
1. 全セクションの詳細読了
2. PROJECT_STRUCTURE.md - "CSRF トークン管理" 詳細
3. AUTHENTICATION_GUIDE.md - コード例・テスト例
4. API_ROUTES.md - 全リクエスト/レスポンス例
```

---

## 🔗 関連ドキュメント

### プロジェクト内の他ドキュメント

- `DOCKER_DEPLOYMENT.md` - Docker デプロイメント詳細
- `WSL2_QUICK_START.md` - WSL2 環境セットアップ
- `ENVIRONMENT_SWITCHING.md` - 環境切り替えガイド
- `README.md` - プロジェクト基本情報

### 外部リソース

- [Laravel 11 ドキュメント](https://laravel.com/docs)
- [Inertia.js ドキュメント](https://inertiajs.com/)
- [Vue 3 ドキュメント](https://vuejs.org/)
- [Tailwind CSS ドキュメント](https://tailwindcss.com/)

---

**最終更新**: 2025年12月9日  
**版**: 1.0
