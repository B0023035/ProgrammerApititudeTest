# 別のCloudflareアカウント・ドメイン用セットアップ - 総まとめ

**📅 作成日**: 2026-03-05  
**📌 対象**: 別のCloudflareアカウント・ドメインでのセットアップ  
**⏱️ 所要時間**: 約1～2時間

---

## 🎯 このガイドについて

このドキュメントセットは、既存の Cloudflare Tunnel セットアップを、**別のCloudflareアカウント** と **別のドメイン** で再現するための完全ガイドです。

**重要なポイント**:
- ✅ ファイル内容は変更しません（ドキュメントのみ）
- ✅ すべてが新しい環境で正しく動作するように設計
- ✅ エラーを最小化するため小分けで段階的に進めます
- ✅ 変更が必要なファイルをすべて網羅

---

## 📚 ドキュメント構成

### 概要・準備

1. **[MULTI_ACCOUNT_SETUP_OVERVIEW.md](MULTI_ACCOUNT_SETUP_OVERVIEW.md)**
   - 変更が必要なファイル一覧
   - 優先度リスト
   - チェックリスト

### 段階的セットアップ

2. **[MULTI_ACCOUNT_SETUP_PART1.md](MULTI_ACCOUNT_SETUP_PART1.md)**
   - `.env` ファイル変更
   - `.env.production` ファイル変更
   - DB パスワード変更

3. **[MULTI_ACCOUNT_SETUP_PART2.md](MULTI_ACCOUNT_SETUP_PART2.md)**
   - `docker/default.conf` 変更
   - `docker/default-https.conf` 変更

4. **[MULTI_ACCOUNT_SETUP_PART3.md](MULTI_ACCOUNT_SETUP_PART3.md)**
   - Cloudflare 認証情報更新
   - 新しいトンネル作成
   - config.yml ファイル作成

5. **[MULTI_ACCOUNT_SETUP_PART4.md](MULTI_ACCOUNT_SETUP_PART4.md)**
   - 最終検証
   - 本運用環境起動
   - トラブルシューティング

---

## 🚀 クイックスタート（概要）

### 所要情報の準備（5分）

セットアップ前に以下の情報を決定してください：

```
【新しいCloudflareアカウント情報】
☐ メールアドレス: _______________________________
☐ ドメイン: _______________________________
☐ トンネル名: _______________________________ (例: my-tunnel)

【新しいサーバー環境】
☐ ユーザー名: _______________________________
☐ プロジェクトディレクトリ: /home/[ユーザー名]/ProgrammerAptitudeTest
☐ DB名: _______________________________ (例: myapp_db)
☐ DBユーザー: _______________________________ (例: myapp_user)
☐ DBパスワード: _______________________________ (強力なパスワード)
```

---

### ステップ実行順序

```
ステップ1: OVERVIEW を読む（10分）
    ↓
ステップ2: PART1 を実施 - 環境変数ファイル変更（20分）
    ↓
ステップ3: PART2 を実施 - Nginx設定変更（20分）
    ↓
ステップ4: PART3 を実施 - Cloudflare設定（30分）
    ↓
ステップ5: PART4 を実施 - 検証と本運用起動（30分）
    ↓
✅ セットアップ完了！
```

---

## 📊 変更対象ファイル早見表

### グループ1：優先度 🔴（必須・高優先）

| ファイル | 変更箇所 | 所要時間 | ガイド |
|---------|--------|--------|-------|
| `.env` | 3 | 5分 | PART1 |
| `.env.production` | 3 | 5分 | PART1 |
| `docker/default.conf` | 6 | 10分 | PART2 |
| `docker/default-https.conf` | 4 | 10分 | PART2 |

### グループ2：優先度 🟠（重要）

| ファイル | 変更箇所 | 所要時間 | ガイド |
|---------|--------|--------|-------|
| `~/.cloudflared/config.yml` | 3 | 15分 | PART3 |

### グループ3：優先度 🟡（推奨）

| ファイル | 変更箇所 | 所要時間 | ガイド |
|---------|--------|--------|-------|
| `.env` (DB) | 2 | 5分 | PART1 |
| `.env.production` (DB) | 4 | 5分 | PART1 |

---

## 🔍 変更内容サマリー

### 1️⃣ ドメイン変更（主要）

すべてのファイルで `aws-sample-minmi.click` を **新しいドメイン** に変更します。

```
例：
aws-sample-minmi.click → myapp.example.com
```

**変更ファイル**: `.env`, `.env.production`, `docker/default.conf`, `docker/default-https.conf`, `config.yml`

---

### 2️⃣ Cloudflare Tunnel情報更新

```
前: minmi-tunnel / 60eb9d8c-3154-4ff8-8192-b78045564bc3
    ↓
後: 【新しいトンネル名】 / 【新しいトンネルID】
```

**変更ファイル**: `~/.cloudflared/config.yml`

---

### 3️⃣ ユーザーパス更新

```
前: /home/b0023035/...
    ↓
後: /home/【新しいユーザー名】/...
```

**変更ファイル**: `~/.cloudflared/config.yml`

---

### 4️⃣ データベース設定更新（推奨）

```
前: DB_DATABASE=programmer_test / DB_USERNAME=programmer_user
    ↓
後: DB_DATABASE=【新しいDB名】 / DB_USERNAME=【新しいDBユーザー】
```

**変更ファイル**: `.env`, `.env.production`

---

## ✅ 実行前チェックリスト

各ステップ前に以下を確認してください：

### ステップ1実行前

- [ ] PART1 ドキュメントを読んだ
- [ ] 新しいドメイン名が決定している
- [ ] 新しいDB情報が決定している

### ステップ2実行前

- [ ] PART1 を完了している
- [ ] PART2 ドキュメントを読んだ
- [ ] Nginx設定ファイルが存在していることを確認

### ステップ3実行前

- [ ] PART2 を完了している
- [ ] PART3 ドキュメントを読んだ
- [ ] 新しいCloudflareアカウントでログイン可能か確認
- [ ] 新しいドメインがCloudflareに登録されているか確認
- [ ] cloudflared がインストールされているか確認

### ステップ4実行前

- [ ] PART3 を完了している
- [ ] PART4 ドキュメントを読んだ
- [ ] Docker コンテナが起動しているか確認

---

## 🎓 各ファイルの役割理解

### `.env` ファイル

**役割**: ローカル開発環境用の環境変数

```env
APP_URL=https://【新しいドメイン】
SESSION_DOMAIN=【新しいドメイン】
```

**編集方法**: テキストエディタで直接編集

---

### `.env.production` ファイル

**役割**: 本番環境用の環境変数

```env
APP_URL=https://【新しいドメイン】
SESSION_DOMAIN=【新しいドメイン】
SANCTUM_STATEFUL_DOMAINS=【新しいドメイン】,www.【新しいドメイン】
```

**編集方法**: テキストエディタで直接編集

---

### `docker/default.conf` ファイル

**役割**: Nginx HTTPS設定

```nginx
server_name 【新しいドメイン】 www.【新しいドメイン】;
ssl_certificate /etc/letsencrypt/live/【新しいドメイン】/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/【新しいドメイン】/privkey.pem;
```

**編集方法**: テキストエディタで直接編集

---

### `~/.cloudflared/config.yml` ファイル

**役割**: Cloudflare Tunnel設定

```yaml
tunnel: 【新しいトンネル名】
credentials-file: /home/【新しいユーザー名】/.cloudflared/【新しいトンネルID】.json
ingress:
  - hostname: 【新しいドメイン】
```

**作成方法**: 新しく作成 / テキストエディタ手動作成

---

## 🔐 セキュリティ注意事項

### データベースパスワード

```
⚠️ 必ず強力なパスワードに変更してください
✅ 推奨: 12文字以上 + 特殊文字混在
❌ NG: 同じパスワードを使用

生成方法：
$ openssl rand -base64 12
```

---

### Cloudflare認証情報

```
⚠️ ~/.cloudflared/config.yml のファイルパーミッションを確認
✅ chmod 600 ~/.cloudflared/config.yml
❌ NG: chmod 644 など誰でも読める権限
```

---

## 📞 よくある質問

### Q1: 既存の環境はそのまま残る？

**A**: はい。このガイドは **別の環境** を作成するものです。既存環境に影響はありません。

---

### Q2: Docker コンテナを再起動する必要はある？

**A**: はい。環境変数を変更した場合は、Docker コンテナを再起動してください：
```bash
docker compose -f docker-compose.production.yml restart
```

---

### Q3: ファイルをバックアップしておくべき？

**A**: はい。変更前に既存ファイルのバックアップを取ることをお勧めします：
```bash
cp .env .env.backup
cp docker/default.conf docker/default.conf.backup
```

---

### Q4: 変更を取り消したい場合は？

**A**: バックアップから復元してください：
```bash
cp .env.backup .env
cp docker/default.conf.backup docker/default.conf
```

---

## 📋 最終確認チェックリスト

すべてのセットアップが完了したら、以下を確認してください：

- [ ] すべてのファイルが正しく変更されている
- [ ] Docker コンテナが起動している（全て healthy）
- [ ] ローカルホストからアクセスできる
- [ ] Tunnel が起動している
- [ ] 新しいドメインからリモートアクセスできる
- [ ] Tunnel がバックグラウンド起動している
- [ ] ログに エラーが出ていない

---

## 🎉 セットアップ完了後

### 運用開始

```bash
# 確認コマンド
docker compose -f docker-compose.production.yml ps
cloudflared tunnel list
```

### 定期確認

毎週確認することをお勧めします：
```bash
# Tunnel状態確認
sudo systemctl status cloudflared

# ログ確認
sudo journalctl -u cloudflared --since "1 day ago"

# アプリケーション動作確認
curl -s https://【新しいドメイン】/ | head
```

---

## 📞 トラブル時の連絡先情報

各ドキュメントの「トラブルシューティング」セクションを参照してください：

- **PART1**: 環境変数ファイルの問題
- **PART2**: Nginx設定の問題
- **PART3**: Cloudflare設定の問題
- **PART4**: 接続・運用の問題

---

## 🔄 参考資料

### 本ガイドと関連するオリジナルドキュメント

- [CLOUDFLARE_TUNNEL_QUICKSTART.md](CLOUDFLARE_TUNNEL_QUICKSTART.md) - 元の環境のクイックスタート
- [CLOUDFLARE_TUNNEL_SETUP.md](CLOUDFLARE_TUNNEL_SETUP.md) - 元の環境の詳細ガイド
- [CLOUDFLARE_SETUP_GUIDE.md](CLOUDFLARE_SETUP_GUIDE.md) - Cloudflare基本設定

### 外部参考リンク

- [Cloudflare公式ドキュメント](https://developers.cloudflare.com/)
- [Cloudflare Tunnel ドキュメント](https://developers.cloudflare.com/cloudflare-one/connections/connect-applications/)
- [Let's Encrypt](https://letsencrypt.org/)

---

## ✨ セットアップの流れ（完全版）

```
【準備フェーズ】
1. 新しいCloudflareアカウントを作成
2. 新しいドメインを登録
3. 新しいサーバー環境を用意
4. cloudflared をインストール

【実施フェーズ】
5. PART1: 環境変数ファイル変更
6. PART2: Nginx設定ファイル変更
7. PART3: Cloudflare設定の変更
8. PART4: 最終検証と本運用起動

【運用フェーズ】
9. ログの確認
10. 定期メンテナンス
```

---

**このドキュメントセットにはすべてのステップが含まれています。各PART のドキュメントに従って進めてください。**

**重要**: ファイル内容は変更していません。各ステップで手動でファイルを編集してください。
