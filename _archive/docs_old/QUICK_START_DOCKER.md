# 🚀 Docker 公開クイックスタートガイド

このガイドは、最短でプログラマー適性検査システムを公開するための手順です。

**3つの環境から選択できます：**

| 環境                                              | 用途           | 難易度    |
| ------------------------------------------------- | -------------- | --------- |
| 🌐 [ネットワーク対応](#-ネットワーク対応-docker)  | 複数PCでテスト | ⭐ 簡単   |
| 🔒 [開発環境](#-開発環境-docker)                  | 安全な開発     | ⭐ 簡単   |
| 🖥️ [スタンドアロン](#-スタンドアロン-docker-なし) | Docker不要     | ⭐⭐ 中級 |

## 📋 必要なもの

- Docker & Docker Compose (インストール方法: [docker.com/get-started](https://www.docker.com/get-started))
- Git
- テキストエディタ (nano, vi など)
- 本番用サーバー (VPS/自社サーバー推奨)

## ⚡ 5分で本番環境構築

### ステップ1: リポジトリをクローン（ローカル）

```bash
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerAptitudeTest
```

### ステップ2: ローカルでテスト（オプション）

```bash
# テストスクリプト実行
bash test-docker-deployment.sh

# または手動で確認
docker-compose -f docker-compose.prod.yml up -d
# ブラウザで http://localhost にアクセス
docker-compose -f docker-compose.prod.yml down
```

### ステップ3: サーバーにデプロイ

```bash
# サーバーにSSHでアクセス
ssh user@your-server.com

# Docker をインストール
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# プロジェクトをクローン
git clone https://github.com/B0023035/ProgrammerApititudeTest.git
cd ProgrammerAptitudeTest

# 本番環境設定を作成
cp .env.example.production .env

# エディタで設定を編集
nano .env
# 以下を変更:
# - APP_URL=https://your-domain.com
# - DB_PASSWORD (強力なパスワード)
# - MAIL_FROM_ADDRESS

# コンテナを起動
docker-compose -f docker-compose.prod.yml up -d

# マイグレーション実行
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 確認
curl http://localhost/health
```

### ステップ4: SSL/HTTPS 設定（推奨）

```bash
# Let's Encrypt で証明書を取得
sudo apt install -y certbot python3-certbot-nginx
sudo certbot certonly --standalone -d your-domain.com

# docker/default.conf を編集（既に基本設定あり）
nano docker/default.conf

# コンテナを再起動
docker-compose -f docker-compose.prod.yml restart app
```

## 🎯 よくある操作

### コンテナの状態確認

```bash
docker-compose -f docker-compose.prod.yml ps
```

### ログ確認

```bash
docker-compose -f docker-compose.prod.yml logs -f app
```

### コンテナ再起動

```bash
docker-compose -f docker-compose.prod.yml restart
```

### コード更新

```bash
git pull origin main
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

### バックアップ

```bash
# DB バックアップ
docker-compose -f docker-compose.prod.yml exec db \
  mysqldump -u sail -ppassword laravel > backup_$(date +%Y%m%d).sql

# ストレージバックアップ
tar -czf storage_$(date +%Y%m%d).tar.gz storage/
```

## 🐳 Docker コマンドリファレンス

| コマンド                                                  | 説明                           |
| --------------------------------------------------------- | ------------------------------ |
| `docker-compose -f docker-compose.prod.yml up -d`         | 起動                           |
| `docker-compose -f docker-compose.prod.yml down`          | 停止・削除                     |
| `docker-compose -f docker-compose.prod.yml logs -f`       | ログ表示                       |
| `docker-compose -f docker-compose.prod.yml exec app bash` | コンテナにアクセス             |
| `docker-compose -f docker-compose.prod.yml ps`            | ステータス確認                 |
| `docker system prune`                                     | 不要なイメージ・コンテナを削除 |

## 🔍 トラブルシューティング

### ポート 80 が既に使用されている

```bash
# 何が使用しているか確認
sudo lsof -i :80

# または
sudo netstat -tulpn | grep :80

# 既存のサービスを停止
sudo systemctl stop apache2  # または nginx など
```

### コンテナが起動しない

```bash
# ログを確認
docker-compose -f docker-compose.prod.yml logs app

# 一般的な解決策:
# 1. .env ファイルをチェック
# 2. ディスク容量を確認: df -h
# 3. Docker デーモンを再起動: sudo systemctl restart docker
```

### データベース接続エラー

```bash
# MySQL が起動しているか確認
docker-compose -f docker-compose.prod.yml ps | grep db

# 接続をテスト
docker-compose -f docker-compose.prod.yml exec db \
  mysql -u sail -ppassword -e "SELECT 1;"
```

## 📊 パフォーマンス監視

```bash
# CPU・メモリ使用率を監視
docker stats

# ディスク使用量を確認
docker system df

# ログサイズを確認
du -sh storage/logs/
```

## 🔒 セキュリティチェックリスト

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] HTTPS を有効化
- [ ] 強力なDB パスワード
- [ ] ファイアウォール設定（ポート 80/443 のみ公開）
- [ ] 定期バックアップ設定
- [ ] ログローテーション設定
- [ ] セキュリティアップデートの定期確認

## 📞 さらに詳しい情報

- 詳細ガイド: [DOCKER_DEPLOYMENT.md](./DOCKER_DEPLOYMENT.md)
- デプロイメントガイド: [DEPLOYMENT.md](./DEPLOYMENT.md)
- トラブルシューティング: [DOCKER_DEPLOYMENT.md#トラブルシューティング](./DOCKER_DEPLOYMENT.md#トラブルシューティング)

## 🎓 次のステップ

1. **監視・ロギング**: Datadog、CloudWatch などで監視を設定
2. **自動バックアップ**: cron で定期的にバックアップを実行
3. **ロードバランシング**: 複数サーバーでの展開を検討
4. **CI/CD パイプライン**: GitHub Actions で自動デプロイを設定

---

**公開準備完了！ 🎉**
