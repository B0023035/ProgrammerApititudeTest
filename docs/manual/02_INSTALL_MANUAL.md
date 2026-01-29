# インストールマニュアル

プログラマー適性試験システムを新しい環境にインストールする手順を説明します。

## 目次

1. [システム要件](#システム要件)
2. [インストール手順](#インストール手順)
3. [初期設定](#初期設定)
4. [動作確認](#動作確認)
5. [トラブルシューティング](#トラブルシューティング)

---

## システム要件

### 必須ソフトウェア

| ソフトウェア   | バージョン | インストール方法                                                                  |
| -------------- | ---------- | --------------------------------------------------------------------------------- |
| Docker         | 20.10以上  | [公式サイト](https://docs.docker.com/get-docker/)                                 |
| Docker Compose | 2.0以上    | Docker Desktopに含まれる                                                          |
| Git            | 2.30以上   | `apt install git` または [公式サイト](https://git-scm.com/)                       |

### 推奨スペック

| 項目   | 最小     | 推奨      |
| ------ | -------- | --------- |
| CPU    | 2コア    | 4コア以上 |
| メモリ | 4GB      | 8GB以上   |
| ストレージ | 10GB   | 20GB以上  |

### 固定バージョン

このシステムは以下のバージョンで動作確認済みです：

| コンポーネント | バージョン      |
| -------------- | --------------- |
| PHP            | 8.2.15          |
| MySQL          | 8.0.36          |
| Redis          | 7.2-alpine      |
| Node.js        | 20.11           |
| Composer       | 2.7             |

---

## インストール手順

### 方法1: 自動インストール（推奨）

#### 1. プロジェクトファイルの配置

```bash
# ZIPファイルの場合
unzip ProgrammerAptitudeTest.zip
cd ProgrammerAptitudeTest

# Gitの場合
git clone [リポジトリURL] ProgrammerAptitudeTest
cd ProgrammerAptitudeTest
```

#### 2. 環境設定ファイルの作成

```bash
cp .env.example .env
```

#### 3. インストールスクリプトの実行

```bash
chmod +x install.sh
./install.sh
```

インストールが完了すると、以下のメッセージが表示されます：

```
==========================================
インストールが完了しました！
==========================================

アクセスURL:
  ローカル: http://localhost/
  管理画面: http://localhost/admin/login

初期管理者アカウント:
  メール: admin@provisional
  パスワード: P@ssw0rd

※ 初回ログイン後、パスワードとメールアドレスを変更してください。
```

---

### 方法2: 手動インストール

#### 1. プロジェクトファイルの配置

```bash
unzip ProgrammerAptitudeTest.zip
cd ProgrammerAptitudeTest
```

#### 2. 環境設定ファイルの作成

```bash
cp .env.example .env
```

#### 3. Dockerイメージのビルド

```bash
docker compose -f docker-compose.production.yml build
```

#### 4. コンテナの起動

```bash
docker compose -f docker-compose.production.yml up -d
```

#### 5. データベースの準備を待機（約30秒）

```bash
sleep 30
```

#### 6. Nginx設定の適用

```bash
docker exec programmer-test-app sed -i 's|fastcgi_pass unix:/var/run/php-fpm.sock;|fastcgi_pass 127.0.0.1:9000;|g' /etc/nginx/http.d/default.conf
docker exec programmer-test-app nginx -s reload
```

#### 7. APP_KEYの生成（.envが空の場合）

```bash
docker exec programmer-test-app php artisan key:generate
```

#### 8. データベースのマイグレーション

```bash
docker exec programmer-test-app php artisan migrate --force
```

#### 9. 初期データの投入

```bash
docker exec programmer-test-app php artisan db:seed --force
```

#### 10. キャッシュの生成

```bash
docker exec programmer-test-app php artisan config:cache
docker exec programmer-test-app php artisan route:cache
docker exec programmer-test-app php artisan view:cache
```

---

## 初期設定

### 初期管理者アカウント

| 項目         | 値                |
| ------------ | ----------------- |
| メール       | admin@provisional |
| パスワード   | P@ssw0rd          |

> ⚠️ **重要**: 初回ログイン後、必ずパスワードとメールアドレスを変更してください。

### パスワードの変更手順

1. `http://localhost/admin/login` にアクセス
2. 初期アカウントでログイン
3. 右上のユーザー名をクリック → 「アカウント設定」
4. 新しいメールアドレスとパスワードを入力
5. 「保存」をクリック

---

## 動作確認

### コンテナの状態確認

```bash
docker compose -f docker-compose.production.yml ps
```

期待される出力：
```
NAME                      STATUS
programmer-test-app       Up (healthy)
programmer-test-db        Up (healthy)
programmer-test-redis     Up (healthy)
```

### ウェブアクセス確認

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost/
# 期待: 200
```

### データベース確認

```bash
docker exec programmer-test-db mysql -u sail -ppassword laravel -e "SELECT COUNT(*) FROM questions;"
# 期待: 95（問題数）
```

---

## トラブルシューティング

### 問題: コンテナが起動しない

**原因**: ポート80が使用中

**解決策**:
```bash
# 使用中のプロセスを確認
sudo lsof -i :80

# Apacheを停止
sudo service apache2 stop

# または.envでポートを変更
# APP_PORT=8080
```

### 問題: 500エラーが発生

**原因**: APP_KEYが設定されていない

**解決策**:
```bash
docker exec programmer-test-app php artisan key:generate
docker exec programmer-test-app php artisan config:cache
```

### 問題: データベース接続エラー

**原因**: データベースの起動が完了していない

**解決策**:
```bash
# データベースの状態確認
docker compose -f docker-compose.production.yml logs db

# 30秒待ってから再試行
sleep 30
docker exec programmer-test-app php artisan migrate --force
```

### 問題: Nginx 502 Bad Gateway

**原因**: PHP-FPMの接続設定

**解決策**:
```bash
docker exec programmer-test-app sed -i 's|fastcgi_pass unix:/var/run/php-fpm.sock;|fastcgi_pass 127.0.0.1:9000;|g' /etc/nginx/http.d/default.conf
docker exec programmer-test-app nginx -s reload
```

### 問題: 問題データがない

**原因**: シーダーが実行されていない

**解決策**:
```bash
docker exec programmer-test-app php artisan db:seed --force
```

---

## アンインストール

システムを完全に削除する場合：

```bash
# コンテナとボリュームを削除
docker compose -f docker-compose.production.yml down -v

# イメージを削除
docker rmi programmeraptitudetest-app

# プロジェクトフォルダを削除
cd ..
rm -rf ProgrammerAptitudeTest
```

---

## 次のステップ

- [操作マニュアル](04_OPERATION_MANUAL.md) - システムの使い方
- [環境設定マニュアル](05_ENVIRONMENT_SETUP.md) - 詳細な環境設定
