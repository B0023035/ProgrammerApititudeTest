# プログラマー適性検査システム

> **オンライン適性試験プラットフォーム** - Laravel + Vue 3 で構築された、エンジニア採用向けの包括的な試験管理システム

![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20.svg)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D.svg)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0-005E87.svg)

---

## 🎯 システムの特徴

### 📊 **包括的な試験管理機能**

- **複合形式対応**: 3 パート構成の試験実施（Part 1, 2, 3）
- **練習問題機能**: 本番前の学習機会を提供
- **リアルタイムタイマー**: パート別の時間制限管理
- **自動採点**: 即座に正誤判定・成績集計
- **成績レポート**: 受験者別・統計分析機能

### 🔐 **エンタープライズレベルのセキュリティ**

- **デュアル認証**: ユーザー・管理者ガード分離
- **不正検知システム**:
    - ウィンドウ/タブ切り替え検出
    - キーボードショートカット禁止
    - 画面キャプチャ試行検出
    - リアルタイム違反ログ
- **CSRF トークン保護**: 多層的なトークン管理
- **セッション管理**: データベースバックドセッション保存
- **試験コード検証**: パスフレーズベースのアクセス制御

### ⚡ **高速・スケーラブル**

- **SPA アーキテクチャ**: Inertia.js による高速ナビゲーション
- **レスポンシブ設計**: モバイル・タブレット対応
- **Redis キャッシング**: 高スループット対応
- **Docker コンテナ化**: 統一環境での即座デプロイ

### 👥 **ユーザーフレンドリー**

- **ゲストアクセス**: 登録なしで試験可能
- **練習から本番へ**: スムーズなユーザージャーニー
- **直感的 UI**: Tailwind CSS による美しい設計
- **多言語対応準備**: 国際的利用を想定

### 📈 **管理者向け機能**

- **ダッシュボード**: リアルタイム受験者監視
- **イベント管理**: 試験日程・パラメータ設定
- **問題管理**: 問題・選択肢の作成・編集・削除
- **成績分析**: CSV エクスポート・統計レポート
- **ユーザー管理**: 受験者情報・成績確認

---

## 🚀 クイックスタート

### 前提条件

- **Docker & Docker Compose** (最新版推奨)
- **Git**
- **ポート 80, 5173, 3306 が利用可能**

### 30 秒で起動

#### 方法 1: 自動セットアップ（推奨）

```bash
# リポジトリクローン
git clone https://github.com/yourusername/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest

# ワンステップセットアップ & 起動
docker compose up -d && docker compose exec -T laravel.test php artisan migrate --force
```

#### 方法 2: 手動セットアップ

```bash
# 1. 環境変数ファイル作成
cp .env.example .env

# 2. Docker コンテナ起動
docker compose up -d

# 3. Laravel 初期化
docker compose exec -T laravel.test composer install
docker compose exec -T laravel.test php artisan key:generate
docker compose exec -T laravel.test php artisan migrate --force

# 4. フロントエンドビルド
docker compose exec -T laravel.test npm install
docker compose exec -T laravel.test npm run build

# 5. キャッシュクリア
docker compose exec -T laravel.test php artisan cache:clear
docker compose exec -T laravel.test php artisan config:cache
```

---

## 🛠️ 使用技術

### バックエンド

```
PHP 8.4 (Laravel 11)
├─ Eloquent ORM (データベース)
├─ Blade テンプレート
├─ Session 管理 (Database driver)
└─ RESTful API (Inertia 経由)
```

### フロントエンド

```
Vue 3 (Composition API)
├─ TypeScript 5.9
├─ Inertia.js v2 (SPA フレームワーク)
├─ Tailwind CSS 3.2 (スタイリング)
├─ Vite 6.2 (ビルドツール)
└─ Axios (HTTP クライアント)
```

### インフラ

```
Docker Compose
├─ PHP 8.4 (Laravel Sail)
├─ MySQL 8.0 (データベース)
├─ Redis 7 (キャッシング)
└─ Nginx (Web サーバー)
```

### NPM パッケージ一覧

**フロントエンド フレームワーク**:

- `@inertiajs/vue3@2.2.15` - SPA フレームワーク
- `vue@3.5.20` - UI フレームワーク
- `@vue/server-renderer@3.4.0` - サーバーサイドレンダリング
- `axios@1.13.2` - HTTP 通信
- `typescript@5.9.3` - 静的型チェック

**スタイリング**:

- `tailwindcss@3.2.1` - ユーティリティ CSS
- `@tailwindcss/forms@0.5.3` - フォームスタイル
- `@tailwindcss/vite@4.0.0` - Vite インテグレーション
- `postcss@8.4.31` - CSS 処理

**ビルド & 開発**:

- `vite@6.2.4` - 高速ビルドツール
- `@vitejs/plugin-vue@5.0.0` - Vue サポート
- `laravel-vite-plugin@1.2.0` - Laravel 統合
- `vue-tsc@2.0.24` - Vue TypeScript コンパイラ
- `concurrently@9.0.1` - 並行タスク実行

**品質・検査**:

- `eslint@9.39.1` - コード検査
- `eslint-plugin-vue@10.5.1` - Vue ESLint ルール
- `prettier@3.6.2` - コードフォーマッタ
- `@typescript-eslint/eslint-plugin@8.46.3` - TypeScript ESLint
- `@typescript-eslint/parser@8.46.3` - TypeScript パーサ
- `@babel/eslint-parser@7.28.5` - Babel ESLint パーサ

---

## 📍 アクセス URL

起動後、以下のポートでアクセス可能です:

| 機能         | URL                     | 用途                   |
| ------------ | ----------------------- | ---------------------- |
| **Web App**  | `http://localhost`      | メインアプリケーション |
| **Vite Dev** | `http://localhost:5173` | 開発時 HMR             |
| **Database** | `localhost:3306`        | MySQL アクセス         |

**サンプルアカウント**:

```
ユーザーログイン:
  Email: user@example.com
  Password: password123

管理者ログイン:
  Email: admin@example.com
  Password: password123
```

---

## 📊 アピールポイント

### 1️⃣ **プロダクションレディ**

- ✅ Docker コンテナ化で環境統一
- ✅ CSRF・認証保護の多層実装
- ✅ エラーハンドリング・ログ記録
- ✅ セッション管理・キャッシング最適化
- ✅ デプロイメント自動化対応

### 2️⃣ **スケーラブル・メンテナンス性**

- ✅ モダン技術スタック (Laravel 11, Vue 3)
- ✅ TypeScript による型安全開発
- ✅ クリーンなアーキテクチャ (MVC + SPA)
- ✅ 包括的なドキュメント
- ✅ テスト可能な設計

### 3️⃣ **セキュリティ第一**

- ✅ 企業レベルの不正検知
- ✅ 多要素認証対応可能
- ✅ GDPR 対応設計
- ✅ SSL/TLS 統合準備完了
- ✅ 監査ログ完全記録

### 4️⃣ **UX に優れた設計**

- ✅ SPA による高速ナビゲーション
- ✅ リアルタイムタイマー表示
- ✅ プログレッシブエンハンスメント
- ✅ キーボード操作対応
- ✅ アクセシビリティ配慮

### 5️⃣ **コスト効率**

- ✅ オープンソース技術のみ使用
- ✅ インフラストラクチャに依存しない
- ✅ 水平スケーリング対応
- ✅ リソース効率的 (Redis キャッシング)
- ✅ 低総保有コスト (TCO)

---

## 📈 システム構成図

```
┌─────────────────────────────────────────────────────┐
│              ユーザーブラウザ (SPA)                   │
│         Vue 3 + TypeScript + Tailwind CSS           │
└────────────────────┬────────────────────────────────┘
                     │ (Inertia + Axios)
                     ▼
┌─────────────────────────────────────────────────────┐
│            Web Server (Nginx in Docker)             │
├─────────────────────────────────────────────────────┤
│    PHP 8.4 Application Server (Laravel 11)          │
├─────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────────────────┐    │
│  │ Controllers  │  │ Middleware Stack         │    │
│  │ (RESTful)    │  │  ├─ CSRF Protection      │    │
│  ├──────────────┤  │  ├─ Auth Guard           │    │
│  │ Models       │  │  ├─ Session Handler      │    │
│  │ (Eloquent)   │  │  └─ Inertia Props       │    │
│  └──────────────┘  └──────────────────────────┘    │
└────────┬──────────────────────┬─────────────────────┘
         │                      │
    ┌────▼──────┐      ┌────────▼──────┐
    │  MySQL 8  │      │  Redis 7      │
    │ Database  │      │  Cache Layer  │
    └───────────┘      └───────────────┘
```

---

## 🧪 テスト・品質

### コマンド

```bash
# ユニットテスト
docker compose exec -T laravel.test php artisan test

# ESLint (TypeScript)
docker compose exec -T laravel.test npm run lint

# フォーマット
docker compose exec -T laravel.test npm run format
```

---

## 📚 ドキュメント

詳細なドキュメントは以下を参照してください:

| ドキュメント              | 用途                       |
| ------------------------- | -------------------------- |
| `DOCUMENTATION_INDEX.md`  | 📚 全ドキュメント索引      |
| `PROJECT_STRUCTURE.md`    | 🏗️ プロジェクト構成        |
| `MODEL_RELATIONSHIPS.md`  | 📊 データベーススキーマ    |
| `API_ROUTES.md`           | 🛣️ API 仕様                |
| `AUTHENTICATION_GUIDE.md` | 🔐 認証フロー              |
| `DOCKER_DEPLOYMENT.md`    | 🐳 Docker デプロイ         |
| `QUICK_START_DOCKER.md`   | ⚡ Docker クイックスタート |
| `WSL2_QUICK_START.md`     | 🪟 WSL2 セットアップ       |

---

## 🤝 コントリビューション

プルリクエスト・イシュー報告を歓迎します。

```bash
# 開発ブランチで作業
git checkout -b feature/新機能名

# コミット
git commit -am "feat: 新機能の説明"

# プッシュ
git push origin feature/新機能名

# PR 作成
# GitHub UI から PR を作成してください
```

**開発ガイドライン**:

- PSR-12 (PHP) に従う
- Vue 3 Composition API を使用
- TypeScript で型安全性を確保
- ドキュメントを更新する

---

## 📄 ライセンス

MIT License - 詳細は `LICENSE` ファイルを参照

---

## 💬 サポート

- 📖 **ドキュメント**: プロジェクト内のマークダウンファイル
- 🐛 **バグ報告**: GitHub Issues
- 💡 **機能リクエスト**: GitHub Discussions
- 📧 **メールサポート**: support@example.com

---

## 🎓 技術サポート情報

### システム動作環境

**推奨スペック**:

- CPU: 2 コア以上
- メモリ: 4GB 以上
- ストレージ: 10GB 以上
- ネットワーク: 安定した接続

**サポートブラウザ**:

- Chrome/Chromium 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## 🚀 デプロイメント

### クラウドデプロイ例

#### AWS EC2

```bash
docker compose -f docker-compose.prod.yml up -d
# 詳細: DOCKER_DEPLOYMENT.md 参照
```

#### Heroku

```bash
heroku create your-app-name
heroku config:set BUILDPACK_URL=https://github.com/heroku/heroku-buildpack-docker.git
git push heroku main
```

#### DigitalOcean App Platform

```bash
# app.yaml で設定
docker-compose -f docker-compose.prod.yml up -d
```

---

## 📞 お問い合わせ

- 🌐 Web: https://example.com
- 📧 Email: info@example.com
- 🐙 GitHub: https://github.com/yourusername/ProgrammerAptitudeTest
- 💼 LinkedIn: https://linkedin.com/company/yourcompany

---

**最終更新**: 2025年12月10日

**バージョン**: 1.0.0

**著作権**: © 2025 Programming Aptitude Test System. All rights reserved.
