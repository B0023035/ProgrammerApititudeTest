# プログラマー適性試験システム - ドキュメント

## 概要

このフォルダには、プログラマー適性試験システムの運用・保守に必要なドキュメントが含まれています。

---

## ドキュメント一覧

| ファイル                                               | 内容                     | 対象者                 |
| ------------------------------------------------------ | ------------------------ | ---------------------- |
| [01_STARTUP_GUIDE.md](01_STARTUP_GUIDE.md)             | 起動・停止方法           | 運用担当者             |
| [02_MODULE_ARCHITECTURE.md](02_MODULE_ARCHITECTURE.md) | モジュール構成図         | 開発者                 |
| [03_SYSTEM_ARCHITECTURE.md](03_SYSTEM_ARCHITECTURE.md) | システム構成図           | 開発者・インフラ担当   |
| [04_OPERATION_MANUAL.md](04_OPERATION_MANUAL.md)       | 操作マニュアル           | 管理者・受験者・ゲスト |
| [05_ENVIRONMENT_SETUP.md](05_ENVIRONMENT_SETUP.md)     | 環境設定マニュアル       | インフラ担当・開発者   |
| [06_MIGRATION_GUIDE.md](06_MIGRATION_GUIDE.md)         | 他PC・アカウントへの移行 | インフラ担当           |

---

## クイックスタート

### 本番環境の起動

```bash
# 1. Dockerを起動
sudo service docker start

# 2. プロジェクトディレクトリに移動
cd /home/[ユーザー名]/ProgrammerAptitudeTest

# 3. コンテナを起動
docker compose -f docker-compose.production.yml up -d

# 4. Cloudflare Tunnelを起動（外部公開時）
cloudflared tunnel run minmi-tunnel
```

### アクセスURL

| 環境                   | URL                                        |
| ---------------------- | ------------------------------------------ |
| ローカル               | https://localhost/                         |
| 外部（Cloudflare経由） | https://aws-sample-minmi.click/            |
| 管理画面               | https://aws-sample-minmi.click/admin/login |

---

## 技術スタック

| カテゴリ         | 技術                           |
| ---------------- | ------------------------------ |
| バックエンド     | Laravel 11 (PHP 8.3)           |
| フロントエンド   | Vue.js 3 + Inertia.js          |
| データベース     | MySQL 8.0                      |
| キャッシュ       | Redis 7                        |
| Webサーバー      | Nginx                          |
| コンテナ         | Docker + Docker Compose        |
| CDN/セキュリティ | Cloudflare + Cloudflare Tunnel |

---

## サポート

問題が発生した場合は、各ドキュメントのトラブルシューティングセクションを参照してください。

---

## 更新履歴

| 日付       | 内容     |
| ---------- | -------- |
| 2026-01-29 | 初版作成 |
