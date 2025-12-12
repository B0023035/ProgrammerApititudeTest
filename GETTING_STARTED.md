# 起動ガイド・コマンドリファレンス

## ⚡ クイック起動

### ワンステップ起動（最速）

```bash
docker compose up -d && docker compose exec -T laravel.test php artisan migrate --force
```

**結果**:

- Web: `http://localhost`
- Vite Dev: `http://localhost:5173`

---

## 🚀 詳細な起動プロセス

### ステップ 1: リポジトリ取得

```bash
git clone https://github.com/yourusername/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest
```

### ステップ 2: Docker コンテナ起動

```bash
# コンテナ起動（バックグラウンド）
docker compose up -d

# または、ログを確認しながら起動
docker compose up
```

**コンテナ確認**:

```bash
docker compose ps

# 出力例:
# NAME              SERVICE      STATUS
# laravel.test      laravel      Up 2 seconds
# mysql             mysql        Up 3 seconds
# redis             redis        Up 2 seconds
```

### ステップ 3: Laravel セットアップ

```bash
# PHP 依存関係インストール
docker compose exec -T laravel.test composer install

# Application Key 生成
docker compose exec -T laravel.test php artisan key:generate

# マイグレーション実行
docker compose exec -T laravel.test php artisan migrate --force
```

### ステップ 4: Node.js セットアップ

```bash
# NPM 依存関係インストール
docker compose exec -T laravel.test npm install

# フロントエンドビルド
docker compose exec -T laravel.test npm run build
```

### ステップ 5: アクセス

```bash
# Web ブラウザ
open http://localhost

# または
curl http://localhost
```

---

## 📦 NPM スクリプト

### 利用可能なコマンド

```bash
# 開発サーバー起動 (HMR 有効)
npm run dev

# プロダクションビルド
npm run build

# TypeScript チェック
npm run type-check

# ESLint
npm run lint

# Prettier フォーマット
npm run format
```

### 詳細説明

#### 1. `npm run dev` - 開発モード

```bash
docker compose exec -T laravel.test npm run dev
```

**特徴**:

- 🔄 ホットモジュールリプレイスメント (HMR)
- 🐛 ブラウザ DevTools 対応
- 📁 ファイル監視・自動リビルド
- ⚡ 高速開発ビルド

**アクセス**:

- App: `http://localhost`
- Vite: `http://localhost:5173`

---

#### 2. `npm run build` - プロダクションビルド

```bash
docker compose exec -T laravel.test npm run build
```

**実行内容**:

```
1. TypeScript チェック (vue-tsc)
2. Vite ビルド (CSR)
3. Vite ビルド (SSR)
```

**出力**:

```
public/build/
├── assets/
│   ├── app.*.js
│   ├── app.*.css
│   └── ...
└── ssr/
    └── ssr.js
```

**ビルド時間**: 約 10-20 秒

---

#### 3. `npm run type-check` - TypeScript 型チェック

```bash
docker compose exec -T laravel.test npm run type-check
```

**チェック内容**:

- 型チェック
- インターフェース検証
- Inertia Props 検証

---

#### 4. `npm run lint` - ESLint

```bash
docker compose exec -T laravel.test npm run lint
```

**チェック対象**:

- TypeScript ファイル
- Vue コンポーネント
- JavaScript

---

#### 5. `npm run format` - Prettier フォーマット

```bash
docker compose exec -T laravel.test npm run format
```

**フォーマット対象**:

- TypeScript/JavaScript
- Vue テンプレート
- JSON
- Markdown

---

## 🐳 Docker コマンドリファレンス

### 基本コマンド

```bash
# コンテナ起動
docker compose up -d

# コンテナ停止
docker compose stop

# コンテナ削除
docker compose down

# ボリューム含めて削除
docker compose down -v

# ログ確認
docker compose logs -f laravel.test

# コンテナシェル進入
docker compose exec laravel.test bash
```

### Laravel コマンド

```bash
# Artisan コマンド実行（一般形式）
docker compose exec -T laravel.test php artisan <command>

# キャッシュクリア
docker compose exec -T laravel.test php artisan cache:clear

# 設定キャッシュ
docker compose exec -T laravel.test php artisan config:cache

# マイグレーション（フォース）
docker compose exec -T laravel.test php artisan migrate --force

# マイグレーション ロールバック
docker compose exec -T laravel.test php artisan migrate:rollback --force

# シード実行
docker compose exec -T laravel.test php artisan db:seed

# テスト実行
docker compose exec -T laravel.test php artisan test
```

### 開発用コマンド

```bash
# データベースリセット（開発用）
docker compose exec -T laravel.test php artisan migrate:fresh --seed

# ストレージリンク作成
docker compose exec -T laravel.test php artisan storage:link

# ファイル権限修正
docker compose exec -T laravel.test chown -R sail:sail /var/www/html/storage
docker compose exec -T laravel.test chmod -R 777 /var/www/html/storage
```

---

## 🔧 Vite 開発サーバー

### 自動起動

```bash
# Vite 開発サーバー自動起動
docker compose exec -T laravel.test npm run dev &
```

### 手動構成（advanced）

```bash
# Vite 設定確認
cat vite.config.js

# カスタムホスト指定
docker compose exec -T laravel.test npm run dev -- --host 0.0.0.0

# カスタムポート指定
docker compose exec -T laravel.test npm run dev -- --port 3000
```

---

## 📊 使用中の NPM パッケージ

### フロントエンド (30 個)

**コア フレームワーク** (3):

```
@inertiajs/vue3@2.2.15
@inertiajs/inertia@0.11.1
vue@3.5.20
```

**UI/スタイル** (4):

```
tailwindcss@3.2.1
@tailwindcss/forms@0.5.3
@tailwindcss/vite@4.0.0
postcss@8.4.31
autoprefixer@10.4.12
```

**ビルド** (6):

```
vite@6.2.4
@vitejs/plugin-vue@5.0.0
laravel-vite-plugin@1.2.0
vue-tsc@2.0.24
@vue/server-renderer@3.4.0
```

**HTTP/通信** (1):

```
axios@1.13.2
```

**開発ツール** (9):

```
eslint@9.39.1
eslint-plugin-vue@10.5.1
prettier@3.6.2
@typescript-eslint/eslint-plugin@8.46.3
@typescript-eslint/parser@8.46.3
@babel/eslint-parser@7.28.5
@vue/eslint-config-prettier@10.2.0
vue-eslint-parser@10.2.0
concurrently@9.0.1
```

**TypeScript** (1):

```
typescript@5.9.3
```

---

## 📋 スクリプト詳細

### build スクリプト

```bash
"build": "vue-tsc && vite build && vite build --ssr"
```

**実行順**:

1. `vue-tsc` - TypeScript 型チェック
2. `vite build` - CSR (Client-Side Rendering) ビルド
3. `vite build --ssr` - SSR (Server-Side Rendering) ビルド

**出力ファイル**:

- `public/build/` - CSR ファイル
- `bootstrap/ssr/` - SSR ファイル

---

### dev スクリプト

```bash
"dev": "vite"
```

**機能**:

- ホットモジュールリプレイスメント (HMR)
- ファイル監視
- 自動リフレッシュ

**起動**:

```bash
docker compose exec -T laravel.test npm run dev
```

---

## 🔄 起動フロー チェックリスト

### 初回セットアップ

- [ ] Git リポジトリクローン
- [ ] Docker インストール確認
- [ ] `docker compose up -d` でコンテナ起動
- [ ] `composer install` で PHP 依存関係インストール
- [ ] `php artisan key:generate` で App Key 生成
- [ ] `php artisan migrate --force` でマイグレーション実行
- [ ] `npm install` で Node 依存関係インストール
- [ ] `npm run build` でビルド
- [ ] `http://localhost` にアクセス確認

### 日常開発

- [ ] `docker compose up -d` コンテナ起動
- [ ] `npm run dev` 開発サーバー起動（ターミナル別）
- [ ] ブラウザで `http://localhost` にアクセス
- [ ] 開発開始
- [ ] `npm run lint` でコード品質チェック
- [ ] `docker compose down` で終了

---

## 🐛 トラブルシューティング

### ポート競合エラー

```bash
# ポート確認
lsof -i :80
lsof -i :5173
lsof -i :3306

# 別ポートで起動
docker compose -f docker-compose.yml -e APP_PORT=8000 up -d
```

### コンテナ起動エラー

```bash
# ログ確認
docker compose logs laravel.test

# キャッシュクリア
docker system prune -a

# 再度起動
docker compose up -d
```

### NPM パッケージエラー

```bash
# パッケージ再インストール
docker compose exec -T laravel.test rm -rf node_modules package-lock.json
docker compose exec -T laravel.test npm install
```

### マイグレーションエラー

```bash
# ロールバック
docker compose exec -T laravel.test php artisan migrate:rollback --force

# 再実行
docker compose exec -T laravel.test php artisan migrate --force
```

---

## 📈 パフォーマンス最適化

### プロダクション起動

```bash
# プロダクション環境変数設定
cat .env.production > .env

# コンテナ起動（プロダクション設定）
docker compose -f docker-compose.prod.yml up -d

# キャッシング有効化
docker compose exec -T laravel.test php artisan config:cache
docker compose exec -T laravel.test php artisan route:cache

# フロントエンドビルド
docker compose exec -T laravel.test npm run build
```

### 開発時の軽量起動

```bash
# 不要なサービス除外
docker compose up -d laravel.test mysql redis

# または最小構成で
docker compose -f docker-compose.dev.yml up -d
```

---

## 📞 さらにヘルプが必要？

```bash
# Docker Compose ヘルプ
docker compose --help

# Laravel Artisan ヘルプ
docker compose exec -T laravel.test php artisan --help

# NPM ヘルプ
docker compose exec -T laravel.test npm --help

# 特定コマンドのヘルプ
docker compose exec -T laravel.test php artisan make:model --help
```

---

**最終更新**: 2025年12月10日
