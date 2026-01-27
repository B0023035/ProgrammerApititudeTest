# 操作マニュアル

## 目次
1. [システム起動・停止](#1-システム起動停止)
2. [管理者向け操作](#2-管理者向け操作)
3. [受験者向け操作](#3-受験者向け操作)
4. [トラブルシューティング](#4-トラブルシューティング)
5. [URL変更手順](#5-url変更手順)

---

## 1. システム起動・停止

### 1.1 システム起動

```bash
# プロジェクトディレクトリに移動
cd /home/b0023035/ProgrammerAptitudeTest

# 本番環境を起動
docker compose -f docker-compose.prod-test.yml up -d
```

起動確認：
```bash
docker ps
```

以下のコンテナが動作していることを確認：
- `prog-test-prod-app` (Webアプリケーション)
- `prog-test-prod-db` (MySQL)
- `prog-test-prod-redis` (Redis)
- `prog-test-prod-phpmyadmin` (phpMyAdmin ※任意)

### 1.2 システム停止

```bash
cd /home/b0023035/ProgrammerAptitudeTest
docker compose -f docker-compose.prod-test.yml down
```

### 1.3 システム再起動（変更を反映する場合）

```bash
cd /home/b0023035/ProgrammerAptitudeTest

# コードを変更した場合は再ビルドが必要
docker compose -f docker-compose.prod-test.yml down
docker compose -f docker-compose.prod-test.yml build --no-cache prod-app
docker compose -f docker-compose.prod-test.yml up -d

# 単純な再起動（コード変更がない場合のみ）
# docker compose -f docker-compose.prod-test.yml restart
```

### 1.4 ログ確認

```bash
# アプリケーションログ
docker logs prog-test-prod-app --tail 100

# データベースログ
docker logs prog-test-prod-db --tail 100

# リアルタイムログ監視
docker logs -f prog-test-prod-app
```

---

## 2. 管理者向け操作

### 2.1 管理者ログイン

1. ブラウザで `http://10.146.223.35/admin/login` にアクセス
2. 管理者メールアドレスとパスワードを入力
3. 「ログイン」ボタンをクリック

### 2.2 イベント（試験会）管理

#### イベント作成
1. 管理者ダッシュボードから「イベント管理」をクリック
2. 「新規作成」ボタンをクリック
3. 以下の情報を入力：
   - イベント名
   - 説明
   - 開始日時・終了日時
   - 最大受験者数
   - 合言葉（自動生成または手動入力）
4. 「作成」ボタンをクリック

#### イベント開始
1. イベント一覧から対象イベントを選択
2. 「開始」ボタンをクリック
3. 受験者にアクセスURLと合言葉を共有

#### イベント終了
1. イベント一覧から対象イベントを選択
2. 「終了」ボタンをクリック

### 2.3 問題管理

#### 問題追加
1. 管理者ダッシュボードから「問題管理」をクリック
2. 「新規作成」ボタンをクリック
3. 以下の情報を入力：
   - 部（第1部/第2部/第3部）
   - 問題番号
   - 問題文
   - 選択肢（複数）
   - 正解
   - 解説
4. 「作成」ボタンをクリック

### 2.4 結果確認

#### 統計表示
1. 管理者ダッシュボードから「結果管理」をクリック
2. 「統計」タブを選択
3. イベントを選択して統計情報を表示

#### 個別結果
1. 結果一覧から受験者を選択
2. 詳細な回答内容と得点を確認

---

## 3. 受験者向け操作

### 3.1 試験開始（ゲストモード）

1. ブラウザで `http://10.146.223.35/` にアクセス
2. 合言葉を入力して「開始」をクリック
3. 氏名・学校名などの情報を入力
4. 練習問題を実施（任意）
5. 「試験開始」をクリック

### 3.2 試験実施

#### 第1部・第2部（横並びレイアウト）
- 左側：問題文と選択肢
- 右上：回答状況表
- 右下：「前の問題」「次の問題」ボタン

#### 第3部（縦並びレイアウト）
- 上：問題文と選択肢
- 下：回答状況表とナビゲーションボタン

#### 回答方法
1. 選択肢をクリックして回答
2. 「次の問題」で次へ進む
3. 回答状況表の番号をクリックして特定の問題にジャンプ可能

#### 試験終了
1. 全問回答後、「終了」ボタンをクリック
2. 確認ダイアログで「はい」を選択
3. 結果画面が表示される

### 3.3 結果確認

- 試験終了後、自動的に結果画面が表示
- 各部の得点と総合得点を確認
- 証明書の表示・印刷が可能

---

## 4. トラブルシューティング

### 4.1 アクセスできない場合

**症状**: ブラウザで502エラーが表示される

**原因と対処**:
1. **コンテナが起動していない**
   ```bash
   docker ps  # コンテナ状態確認
   docker compose -f docker-compose.prod-test.yml up -d  # 起動
   ```

2. **プロキシの問題**
   - Squidプロキシを経由している場合、プロキシ設定を確認
   - 直接接続テスト：
     ```bash
     curl -v http://10.146.223.35/
     ```

### 4.2 セッションが切れる場合

**症状**: 試験中にログアウトされる

**対処**:
1. Redisの状態確認：
   ```bash
   docker exec prog-test-prod-redis redis-cli ping
   ```
2. セッション設定確認：
   ```bash
   docker exec prog-test-prod-app grep SESSION .env
   ```
3. キャッシュクリア：
   ```bash
   docker exec prog-test-prod-app php artisan cache:clear
   docker exec prog-test-prod-app php artisan config:clear
   ```

### 4.3 データベース接続エラー

**症状**: データベース接続に失敗

**対処**:
1. DBコンテナ状態確認：
   ```bash
   docker logs prog-test-prod-db --tail 50
   ```
2. 接続テスト：
   ```bash
   docker exec prog-test-prod-db mysqladmin ping -uroot -prootpassword
   ```

### 4.4 キャッシュ問題

**症状**: 設定変更が反映されない

**対処**:
```bash
docker exec prog-test-prod-app php artisan config:clear
docker exec prog-test-prod-app php artisan cache:clear
docker exec prog-test-prod-app php artisan view:clear
docker exec prog-test-prod-app php artisan config:cache
docker exec prog-test-prod-app php artisan view:cache
```

---

## 5. URL変更手順

URLを変更する場合は、以下の手順を実行してください。

### 5.1 編集が必要なファイル

1. **`.env`** （8行目）
   ```
   APP_URL=http://新しいURL
   ```

2. **`docker-compose.prod-test.yml`** （16行目 - ビルド引数）
   ```yaml
   args:
       APP_URL: http://新しいURL
   ```

3. **`docker-compose.prod-test.yml`** （25行目 - 環境変数）
   ```yaml
   - APP_URL=http://新しいURL
   ```

### 5.2 変更後の再構築

```bash
cd /home/b0023035/ProgrammerAptitudeTest

# コンテナ停止
docker compose -f docker-compose.prod-test.yml down

# イメージ再構築（キャッシュなし）
docker compose -f docker-compose.prod-test.yml build --no-cache prod-app

# コンテナ起動
docker compose -f docker-compose.prod-test.yml up -d
```

### 5.3 変更確認

```bash
# コンテナ内でURL確認
docker exec prog-test-prod-app php artisan config:show app | grep url
```

---

## 付録：よく使うコマンド一覧

| 操作 | コマンド |
|------|---------|
| システム起動 | `docker compose -f docker-compose.prod-test.yml up -d` |
| システム停止 | `docker compose -f docker-compose.prod-test.yml down` |
| システム再起動 | `docker compose -f docker-compose.prod-test.yml restart` |
| ログ確認 | `docker logs prog-test-prod-app --tail 100` |
| コンテナ状態確認 | `docker ps` |
| キャッシュクリア | `docker exec prog-test-prod-app php artisan cache:clear` |
| 設定再キャッシュ | `docker exec prog-test-prod-app php artisan config:cache` |
| DBバックアップ | `docker exec prog-test-prod-db mysqldump -uroot -prootpassword laravel_prod > backup.sql` |
| DBリストア | `cat backup.sql \| docker exec -i prog-test-prod-db mysql -uroot -prootpassword laravel_prod` |
