# 🔄 環境切り替えガイド

このプロジェクトは3つの実行環境に対応しています。用途に応じて切り替えてください。

---

## 📊 環境比較表

| 環境                 | ファイル                 | 接続元         | 用途                       | セキュリティ          |
| -------------------- | ------------------------ | -------------- | -------------------------- | --------------------- |
| **ネットワーク対応** | `docker-compose.yml`     | 他のPC可       | テスト・検証、ラズパイ検討 | ⚠️ ローカルネット限定 |
| **開発環境**         | `docker-compose.dev.yml` | localhost のみ | セキュアな開発             | ✅ 最高               |
| **スタンドアロン**   | 直接実行（Docker なし）  | 0.0.0.0        | Docker 不要な環境          | 要別途設定            |

---

## 🚀 各環境での起動方法

### 1️⃣ ネットワーク対応環境（他のPCから接続可能）

```bash
# 起動
docker-compose up -d

# ホストマシンのIPアドレスを確認
# Windows: ipconfig
# Mac/Linux: ifconfig | grep inet

# 他のPCからアクセス
# http://192.168.x.x  （ホストのIPアドレス）
```

**用途:**

- ✅ テスト・検証段階（複数PC での試験テスト）
- ✅ ラズパイ検討（ネットワーク接続確認）
- ✅ ローカルネットワーク内での本番前テスト

**セキュリティ注意:**

- ⚠️ ローカルネットワーク内のみに限定してください
- ⚠️ インターネットに公開しないでください（`.env` で認証設定）
- ⚠️ 本番環境では使用しないでください

---

### 2️⃣ 開発環境（localhost のみ）

```bash
# 起動
docker-compose -f docker-compose.dev.yml up -d

# ローカルホストのみアクセス可
# http://localhost
```

**用途:**

- ✅ ローカル開発（セキュア）
- ✅ コード修正・デバッグ
- ✅ 自分のPC での一人開発

**セキュリティ:**

- ✅ ホストマシンからのみアクセス可能
- ✅ 他のPCからはアクセス不可（最も安全）
- ✅ ネットワークに公開されない

---

### 3️⃣ スタンドアロン環境（Docker なし）

```bash
# 前提: PHP 8.4、MySQL 8.0、Redis 7 がホストにインストール済み

# 環境設定
cp .env.example .env
nano .env

# データベース初期化
php artisan migrate --force

# 開発サーバー起動
php artisan serve

# 別ターミナルで Vite 起動
npm run dev

# アクセス
# http://0.0.0.0:8000
```

**用途:**

- ✅ Docker がインストールできない環境
- ✅ ホストマシン上での直接実行
- ✅ サーバー環境での本番デプロイ

**前提条件:**

- PHP 8.4 以上
- MySQL 8.0 以上
- Redis 7 以上
- Node.js 18 以上

---

## 🔧 環境の切り替え方法

### 方法① docker-compose.yml を切り替える

```bash
# 現在の環境を停止
docker-compose down

# 別の環境用 docker-compose を指定して起動
docker-compose -f docker-compose.dev.yml up -d
```

### 方法② シンボリックリンクを作成（便利）

```bash
# ネットワーク対応環境を使用
rm docker-compose.yml
ln -s docker-compose.network.yml docker-compose.yml

# 開発環境に切り替え
rm docker-compose.yml
ln -s docker-compose.dev.yml docker-compose.yml
```

### 方法③ alias を設定（さらに便利）

`.bashrc` または `.zshrc` に追加：

```bash
# ネットワーク対応環境
alias dev-network='docker-compose -f docker-compose.yml up -d && echo "✅ Network environment started"'

# 開発環境
alias dev-local='docker-compose -f docker-compose.dev.yml up -d && echo "✅ Local development environment started"'

# 停止
alias dev-stop='docker-compose down && echo "✅ Stopped"'
```

使用例：

```bash
dev-network  # ネットワーク対応で起動
dev-local    # 開発環境で起動
dev-stop     # 停止
```

---

## 🌐 ネットワークアクセステスト

### 他のPCから接続するテスト手順

**ステップ1: ホストマシンのIP確認**

```bash
# Windows
ipconfig

# Mac/Linux
ifconfig | grep "inet " | grep -v 127.0.0.1
```

例: `192.168.1.100`

**ステップ2: 標準設定でコンテナ起動**

```bash
docker-compose up -d
```

**ステップ3: 他のPCからアクセス**

```bash
# 別のPC のブラウザを開く
# http://192.168.1.100
```

**ステップ4: 確認項目**

```
✅ ログインページが表示されるか
✅ ユーザー登録できるか
✅ 試験が実施できるか
✅ 管理画面にアクセスできるか
✅ 統計・グラフが表示されるか
```

---

## 📊 ログ・状態確認

### ネットワーク対応環境でのトラブルシューティング

```bash
# コンテナ状態確認
docker-compose ps

# ネットワークインターフェース確認
docker network ls
docker network inspect bridge

# ファイアウォール設定確認（Linux）
sudo ufw status

# ファイアウォール許可（Linux）
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### Windows でのネットワークアクセス許可

1. **設定** → **プライバシーとセキュリティ** → **Windowsファイアウォール**
2. **ファイアウォールを通したアプリの許可**
3. **変更** → **Docker Desktop** にチェック

---

## ⚡ クイック切り替え手順

### 開発 → ネットワークテストに変更

```bash
# 開発環境を停止
docker-compose -f docker-compose.dev.yml down

# ネットワーク対応環境で起動
docker-compose up -d

# ホストのIPで他のPCからアクセス
```

### ネットワーク → 開発に変更

```bash
# ネットワーク環境を停止
docker-compose down

# 開発環境で起動
docker-compose -f docker-compose.dev.yml up -d

# localhost でアクセス
```

### Docker 削除（完全リセット）

```bash
# コンテナ・ボリューム・ネットワーク削除
docker-compose down -v

# イメージも削除
docker image rm sail-8.4/app
```

---

## 🎯 推奨される使い分け

| シナリオ              | 推奨環境                  | コマンド                                             |
| --------------------- | ------------------------- | ---------------------------------------------------- |
| **1人で自分のPC開発** | 開発環境                  | `docker-compose -f docker-compose.dev.yml up -d`     |
| **複数PCでテスト**    | ネットワーク対応          | `docker-compose up -d`                               |
| **ラズパイ接続確認**  | ネットワーク対応          | `docker-compose up -d`                               |
| **本番デプロイ**      | `docker-compose.prod.yml` | 参照: [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md) |
| **Docker 不要環境**   | スタンドアロン            | `php artisan serve`                                  |

---

## 📝 環境変数設定

各環境で必要な `.env` 設定：

### ネットワーク対応環境

```bash
APP_URL=http://0.0.0.0  # または実際のホストIP
APP_DEBUG=true
APP_ENV=local
```

### 開発環境

```bash
APP_URL=http://localhost
APP_DEBUG=true
APP_ENV=local
```

### スタンドアロン環境

```bash
APP_URL=http://0.0.0.0:8000
APP_DEBUG=true
APP_ENV=local
DB_HOST=127.0.0.1  # ホストマシン上のMySQL
REDIS_HOST=127.0.0.1  # ホストマシン上のRedis
```

---

## 🔒 セキュリティベストプラクティス

### ネットワーク対応環境を使用する場合

```bash
# ✅ 必ず実施
[ ] APP_DEBUG=false に設定
[ ] 強力なパスワードを設定
[ ] ローカルネットワークのみに限定
[ ] ファイアウォール設定

# ❌ しないこと
[ ] インターネットに公開しない
[ ] デフォルトパスワードを使用しない
[ ] 本番環境では使用しない
```

### 開発環境（最も安全）

```bash
# ✅ デフォルト設定で安全
# - localhost のみアクセス可能
# - ネットワークに公開されない
# - 他のPC からアクセス不可
```

---

## 💡 トラブルシューティング

### 「接続がリセットされました」エラー

```bash
# ポート 80 が使用されているか確認
sudo lsof -i :80

# または別のポートで試す
docker-compose down
sed -i 's/- "0.0.0.0:80:80"/- "0.0.0.0:8080:80"/' docker-compose.yml
docker-compose up -d

# http://192.168.x.x:8080 でアクセス
```

### 「接続できません」エラー

```bash
# 1. ホストのファイアウォール確認
ping 192.168.x.x

# 2. ポートが開いているか確認
telnet 192.168.x.x 80

# 3. コンテナが起動しているか確認
docker-compose ps

# 4. ネットワークが接続しているか確認
docker inspect bridge
```

---

**環境切り替えで自由に開発・テストできます！質問があれば教えてください。**
