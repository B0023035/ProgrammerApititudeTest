# 別のCloudflareアカウント・ドメイン用セットアップ - 第3部

## Cloudflare設定ファイルの変更

このドキュメントでは、Cloudflare Tunnel設定ファイル（`~/.cloudflared/config.yml`）の変更方法を説明します。

---

## 📋 前提条件

以下が完了していることを確認してください：

- [ ] 第1部完了：`.env` ファイルの変更
- [ ] 第2部完了：Nginx設定ファイルの変更
- [ ] 新しいCloudflareアカウントが用意されている
- [ ] 新しいドメインがCloudflareに登録されている
- [ ] cloudflared がインストールされている

---

## 🔐 ステップ1：新しいCloudflareアカウントでログイン

### 1.1 既存の認証情報を削除

```bash
# 現在のCloudflare認証情報を削除
rm -rf ~/.cloudflared/

# ディレクトリを再作成
mkdir -p ~/.cloudflared
chmod 700 ~/.cloudflared
```

**確認**:
```bash
# ディレクトリが作成されたことを確認
ls -la ~/.cloudflared/
```

---

### 1.2 新しいアカウントでログイン

```bash
# 新しいCloudflareアカウントでログイン
cloudflared tunnel login
```

**実行後の流れ**:
1. ブラウザが開く（開かない場合はURLをコピーして手動で開く）
2. 新しいCloudflareアカウントでログイン
3. **新しいドメイン** を選択
4. 「Authorize」をクリック
5. 証明書が `/home/【ユーザー名】/.cloudflared/cert.pem` に保存される

**確認**:
```bash
# 認証情報が保存されたか確認
ls -la ~/.cloudflared/cert.pem
# 出力例: -rw-r--r-- 1 b0023035 b0023035 546 Mar  5 00:18 .cloudflared/cert.pem
```

---

## 🌐 ステップ2：新しいトンネルの作成

### 2.1 トンネルを作成

```bash
# 新しいトンネルを作成（トンネル名は自由に決定）
cloudflared tunnel create 【新しいトンネル名】

# 具体例：
# cloudflared tunnel create my-tunnel
```

**期待される出力**:
```
Tunnel credentials written to /home/【ユーザー名】/.cloudflared/【新しいトンネルID】.json
Created tunnel 【新しいトンネル名】 with id 【新しいトンネルID】
```

**重要：トンネルIDをメモ**:
```
トンネル名: _________________________ （例: my-tunnel）
トンネルID: _________________________ （UUID形式の長い文字列）
```

---

### 2.2 トンネル確認

```bash
# トンネル一覧を表示
cloudflared tunnel list

# 期待される出力（例）：
# ID                                   NAME         CREATED              CONNECTIONS
# a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d  my-tunnel    2026-03-05T10:00:00Z  0/4
```

---

## 🔀 ステップ3：DNS設定（Cloudflare Tunnel ルーティング）

### 3.1 DNS ルート追加

```bash
# Cloudflare DNS にルーティング設定を追加
cloudflared tunnel route dns 【新しいトンネル名】 【新しいドメイン】

# 具体例：
# cloudflared tunnel route dns my-tunnel myapp.example.com
```

**期待される出力**:
```
Successfully created route for 【新しいドメイン】
```

---

### 3.2 Cloudflareダッシュボードで確認

1. https://dash.cloudflare.com にアクセス
2. **新しいドメイン** を選択
3. 左メニューから「DNS」をクリック
4. 以下の CNAME レコードが自動作成されていることを確認：

```
Type    Name                  Content
CNAME   【新しいドメイン】    【トンネルID】.cfargotunnel.com
```

---

## 📝 ステップ4：`~/.cloudflared/config.yml` ファイルの作成

### 4.1 ファイルを作成

```bash
# テキストエディタで新しい config.yml を作成
nano ~/.cloudflared/config.yml

# または vim
vim ~/.cloudflared/config.yml
```

### 4.2 ファイル内容を作成

以下の内容をコピーして、**括弧内を自分の値に置き換えて** ペーストします。

```yaml
tunnel: 【新しいトンネル名】
credentials-file: /home/【新しいユーザー名】/.cloudflared/【新しいトンネルID】.json

ingress:
  - hostname: 【新しいドメイン】
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: 【新しいドメイン】
  - service: http_status:404
```

**具体例**:
```yaml
tunnel: my-tunnel
credentials-file: /home/newuser/.cloudflared/a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d.json

ingress:
  - hostname: myapp.example.com
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: myapp.example.com
  - service: http_status:404
```

---

### 4.3 ファイル保存と権限設定

```bash
# ファイルを保存（nano の場合: Ctrl+O → Enter → Ctrl+X）
# または（vim の場合: Esc → :wq → Enter）

# 権限を設定（セキュリティのため）
chmod 600 ~/.cloudflared/config.yml

# 確認
ls -la ~/.cloudflared/config.yml
# 出力例: -rw------- 1 b0023035 b0023035 334 Mar  5 00:18 config.yml
```

---

## 🔍 ステップ5：config.yml の内容確認

### 5.1 ファイル確認

```bash
# ファイルの内容を確認
cat ~/.cloudflared/config.yml
```

**期待される出力**:
```yaml
tunnel: my-tunnel
credentials-file: /home/newuser/.cloudflared/a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d.json

ingress:
  - hostname: myapp.example.com
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
      originServerName: myapp.example.com
  - service: http_status:404
```

---

### 5.2 認証情報ファイルの確認

```bash
# 認証情報ファイルが存在するか確認
ls -la ~/.cloudflared/*.json

# 期待される出力（例）：
# -r-------- 1 b0023035 b0023035 175 Mar  5 00:18 a1b2c3d4-e5f6-4a5b-9c8d-7e6f5a4b3c2d.json
```

---

## 🧪 ステップ6：Tunnel接続テスト（設定ファイルなし）

### 6.1 トンネルが正しく接続できるかテスト

```bash
# トンネルをテスト実行（フォアグラウンド）
cloudflared tunnel run my-tunnel

# または環境変数を指定する方法
TUNNEL_CONFIG_FILE=~/.cloudflared/config.yml cloudflared tunnel run my-tunnel
```

**期待される出力**:
```
2026-03-05T10:00:00Z INF Starting tunnel my-tunnel
2026-03-05T10:00:00Z INF Registering tunnel connection from <location>
2026-03-05T10:00:00Z INF Tunnel registered successfully
2026-03-05T10:00:01Z INF Connected to CDN
2026-03-05T10:00:01Z INF Serving ingress from https://localhost:443
```

---

### 6.2 別ターミナルで接続テスト

テスト実行中のトンネルに対して、**別のターミナル** で以下のコマンドを実行：

```bash
# リモートから新しいドメインへアクセス
curl -s https://【新しいドメイン】/ | head -20

# または
curl -s -I https://【新しいドメイン】/

# 具体例：
# curl -s https://myapp.example.com/ | head -20
```

**期待される出力**:
```
HTTP/1.1 200 OK
(アプリケーションのHTML内容)
```

---

### 6.3 テスト成功後

**テスト実行中のターミナルで Ctrl+C を押して停止**

```
^C
2026-03-05T10:00:00Z INF Tunnel closed
```

---

## ✅ 第3部完了チェックリスト

- [ ] 既存の CloudFlare 認証情報を削除
- [ ] 新しいCloudflareアカウントでログイン完了
- [ ] 新しいトンネルを作成
- [ ] トンネルID をメモ
- [ ] Cloudflare DNS ルート設定完了
- [ ] `~/.cloudflared/config.yml` ファイルを作成
- [ ] config.yml 内のすべての値を正しく設定
- [ ] config.yml ファイルのパーミッション設定（chmod 600）
- [ ] ローカルテスト実行（トンネル接続確認）
- [ ] 別ターミナルからリモートアクセステスト成功

---

## 🚀 次のステップ

これらのCloudflare設定を完了したら、以下の第4部に進みます：

**[MULTI_ACCOUNT_SETUP_PART4.md](MULTI_ACCOUNT_SETUP_PART4.md)** - 最終検証とバックグラウンド起動

---

## ⚠️ トラブルシューティング

### 問題1：「Cannot find tunnel」エラー

```
Error: Cannot find tunnel with ID or name: my-tunnel
```

**対策**:
```bash
# トンネル一覧確認
cloudflared tunnel list

# トンネルが表示されない場合は再作成
cloudflared tunnel create my-tunnel
```

---

### 問題2：接続できない場合

```
# 1. Docker が起動しているか確認
docker compose -f docker-compose.production.yml ps

# 2. ローカルホストからアクセステスト
curl -s https://localhost/ -k | head

# 3. Tunnel ログを確認
# （テスト実行中のターミナルでエラーメッセージ確認）
```

---

### 問題3：「originServerName」エラー

```
TLS error: x509: certificate signed by unknown authority
```

**原因**: 自己署名証明書が許可されていない

**対策**: `config.yml` に以下を確認
```yaml
originRequest:
  noTLSVerify: true  # ← このオプションが必須
```

---

**注意**: このドキュメントではファイルを実際には編集していません。手動でファイルを編集する必要があります。
