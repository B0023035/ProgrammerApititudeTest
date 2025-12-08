# 🔄 3つの環境実行オプション

このプロジェクトは、開発段階から本番デプロイまで、3つの異なる実行環境をサポートしています。

---

## 📊 環境選択フローチャート

```
┌─────────────────────────────┐
│ どのように使いたいですか？   │
└──────────────┬──────────────┘
               │
       ┌───────┴────────┬────────────┐
       │                │            │
       ▼                ▼            ▼
   🌐 複数PC      🔒 1人開発     🖥️ Docker不要
   からアクセス    (セキュア)     (ホスト直接)
       │                │            │
       ▼                ▼            ▼
  ネットワーク対応  開発環境     スタンドアロン
   docker-compose  docker-compose   直接実行
   .yml             .dev.yml         (PHP + MySQL)
```

---

## 🚀 クイックスタート（各環境）

### 🌐 ネットワーク対応（他のPCからアクセス可能）

```bash
# 1. リポジトリクローン
git clone https://github.com/B0023035/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest

# 2. 起動
docker-compose up -d

# 3. テスト
bash test-network-access.sh

# 4. 他のPCからアクセス
# ブラウザで: http://192.168.x.x  （ホストのIPアドレス）
```

**用途:** テスト・検証、複数PCでの試験実施、ラズパイ接続確認

---

### 🔒 開発環境（localhost のみ、最も安全）

```bash
# 1. リポジトリクローン
git clone https://github.com/B0023035/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest

# 2. 起動
docker-compose -f docker-compose.dev.yml up -d

# 3. アクセス
# ブラウザで: http://localhost
```

**用途:** セキュアな開発、コード修正、1人での開発

---

### 🖥️ スタンドアロン（Docker なし）

```bash
# 1. リポジトリクローン
git clone https://github.com/B0023035/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest

# 2. セットアップ（前提: PHP 8.4、MySQL 8.0、Redis 7、Node.js 18）
bash setup-standalone.sh

# 3. 起動（ターミナル1）
php artisan serve

# 4. 起動（ターミナル2）
npm run dev

# 5. アクセス
# ブラウザで: http://localhost:8000
```

**用途:** Docker 不要な環境、ホストマシン上での直接実行、サーバー本番デプロイ

---

## 🔄 環境の切り替え方法

```bash
# 現在の環境を停止
docker-compose down

# 別の環境で起動
docker-compose -f docker-compose.dev.yml up -d
# または
# bash setup-standalone.sh で起動
```

詳細は [ENVIRONMENT_SWITCHING.md](./ENVIRONMENT_SWITCHING.md) を参照

---

## 🎯 推奨される使い分け

| シナリオ | 環境 | コマンド |
|--------|------|--------|
| 複数PCでテスト | ネットワーク対応 | `docker-compose up -d` |
| 自分のPC開発 | 開発環境 | `docker-compose -f docker-compose.dev.yml up -d` |
| ラズパイでテスト | ネットワーク対応 | `docker-compose up -d` |
| Docker 不要 | スタンドアロン | `bash setup-standalone.sh` |
| 本番デプロイ | `docker-compose.prod.yml` | [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md) |

---

## 📚 ドキュメント

- **詳細ガイド**: [ENVIRONMENT_SWITCHING.md](./ENVIRONMENT_SWITCHING.md)
- **Docker 本番デプロイ**: [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md)
- **クイックスタート**: [QUICK_START_DOCKER.md](./QUICK_START_DOCKER.md)

---

## ✅ 環境の確認

```bash
# 現在起動中の環境を確認
docker-compose ps

# または
docker ps
```

---

## 🆘 トラブルシューティング

各環境でのトラブル解決方法は以下を参照してください：
- [ENVIRONMENT_SWITCHING.md](./ENVIRONMENT_SWITCHING.md#-トラブルシューティング)
- [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md#トラブルシューティング)

---

**最適な環境を選択して、開発・テストをスタートしてください！**
