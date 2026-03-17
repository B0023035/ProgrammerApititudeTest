# Cloudflareセットアップガイド

このガイドではCloudflareの初期設定からDNSレコード設定まで、段階的に説明します。

---

## 第1段階：Cloudflareアカウント作成

### 1.1 アカウント登録

1. [Cloudflare公式サイト](https://www.cloudflare.com)にアクセス
2. 右上の「サインアップ」をクリック
3. メールアドレスとパスワードを入力して登録
4. 登録したメールアドレスに確認メールが届く
5. メール内のリンクをクリックしてメール確認を完了

### 1.2 アカウントの初期設定

1. Cloudflareダッシュボードにログイン
2. 以下の情報を確認しておく：
   - アカウントID（後で必要）
   - メール認証状況

---

## 第2段階：ドメイン登録（Cloudflareへの追加）

### 2.1 ドメインの準備

あらかじめ以下を用意しておいてください：
- 取得済みのドメイン名（例：example.com）
- ドメイン登録者のアカウント情報
- 現在のDNSサーバー情報

### 2.2 ドメインをCloudflareに追加

1. Cloudflareダッシュボードで「Add a Site」をクリック
2. ドメイン名を入力（例：example.com）
3. 「Add Site」をクリック
4. プランを選択：
   - **無料プラン**：試験環境に推奨
   - **Pro**：本番環境に推奨
5. 「Confirm plan」で確認

### 2.3 Cloudflareのネームサーバーを確認

セットアップが進むと、Cloudflareが以下の情報を提示します：

```
Nameserver 1: ns1.cloudflare.com
Nameserver 2: ns2.cloudflare.com
```

**これらを次のステップで使用します。メモしておいてください。**

---

## 第3段階：ドメイン登録業者でのネームサーバー変更

### 3.1 ドメイン登録業者にログイン

ドメインを取得した業者（例：お名前.com、Xserverドメイン等）にログインします。

### 3.2 ネームサーバーの変更

1. ドメイン管理画面で「ネームサーバー設定」を探す
2. ネームサーバーを以下に変更：
   - `ns1.cloudflare.com`
   - `ns2.cloudflare.com`
3. 変更を保存

### 3.3 ネームサーバー反映待機

- 反映時間：通常15分～48時間
- 反映確認コマンド：
  ```bash
  nslookup example.com
  # または
  dig example.com
  ```

---

## 第4段階：Cloudflareでのネームサーバー確認

### 4.1 ネームサーバー設定の完了確認

1. Cloudflareダッシュボードに戻る
2. 対象ドメインをクリック
3. 「Overview」タブで「Nameservers」を確認
4. 「Nameserver setup」が「Complete」になるまで待機

---

## 第5段階：DNSレコード設定

### 5.1 基本的なDNSレコード構成

本プロジェクト（AWS、Nginx、Docker環境）の場合、以下のレコードを設定します。

#### 5.1.1 Aレコード（メインドメイン）

| レコード種別 | 名前 | 内容（IPアドレス） | TTL | プロキシ |
|-------------|------|------------------|-----|---------|
| A | @ | XX.XX.XX.XX | Auto | Proxied（Cloudflareマーク） |

**設定手順：**

1. Cloudflareダッシュボードで対象ドメインをクリック
2. 左メニューから「DNS」をクリック
3. 「+ Add record」をクリック
4. 以下を入力：
   - **Type**: A
   - **Name**: @ （またはドメイン名）
   - **IPv4 address**: お使いのサーバーのIPアドレス
   - **TTL**: Auto
   - **Proxy status**: Proxied（オレンジ色の雲アイコン）
5. 「Save」をクリック

#### 5.1.2 WWWサブドメインのAレコード

| レコード種別 | 名前 | 内容（IPアドレス） | TTL | プロキシ |
|-------------|------|------------------|-----|---------|
| A | www | XX.XX.XX.XX | Auto | Proxied（Cloudflareマーク） |

**設定手順：**

1. 「+ Add record」をクリック
2. 以下を入力：
   - **Type**: A
   - **Name**: www
   - **IPv4 address**: お使いのサーバーのIPアドレス
   - **TTL**: Auto
   - **Proxy status**: Proxied
3. 「Save」をクリック

#### 5.1.3 CNAME設定（wwwをルートドメインにリダイレクト）

| レコード種別 | 名前 | ターゲット | TTL | プロキシ |
|-------------|------|-----------|-----|---------|
| CNAME | www | example.com | Auto | Proxied |

**代替案：** Cloudflareの「Page Rules」でリダイレクトを設定する方法もあります。

### 5.2 メール関連レコード（オプション）

メール機能を使用する場合は、以下のレコードを追加します。

#### 5.2.1 MXレコード

| レコード種別 | 名前 | 内容 | 優先度 | TTL |
|-------------|------|------|--------|-----|
| MX | @ | mail.example.com | 10 | Auto |

**設定手順：**

1. 「+ Add record」をクリック
2. 以下を入力：
   - **Type**: MX
   - **Name**: @ 
   - **Mail server**: mail.example.com
   - **Priority**: 10
   - **TTL**: Auto
3. 「Save」をクリック

#### 5.2.2 SPFレコード

| レコード種別 | 名前 | 内容 | TTL |
|-------------|------|------|-----|
| TXT | @ | v=spf1 include:_spf.google.com ~all | Auto |

**設定手順：**

1. 「+ Add record」をクリック
2. 以下を入力：
   - **Type**: TXT
   - **Name**: @
   - **Content**: v=spf1 include:_spf.google.com ~all
   - **TTL**: Auto
3. 「Save」をクリック

### 5.3 SSL/TLS設定の確認

1. 左メニューから「SSL/TLS」をクリック
2. 「Overview」で暗号化レベルを確認
3. 推奨設定：
   - **Encryption mode**: Full（strict）
   - または「Flexible」（初期段階）

---

## 第6段階：Cloudflareセキュリティ設定

### 6.1 SSL/TLSの設定

1. 「SSL/TLS」→「Overview」
2. 暗号化モードを選択：
   ```
   - Off (not recommended): 暗号化なし
   - Flexible: ブラウザ→Cloudflare間は暗号化（初期推奨）
   - Full: Cloudflare→オリジンまで暗号化
   - Full (strict): 自己署名証明書でもOK（推奨）
   ```
3. 本環境ではLet's Encryptで証明書を取得済みなので「Full」を選択

### 6.2 HTTPSリダイレクト

1. 「SSL/TLS」→「Edge Certificates」
2. 「Always Use HTTPS」を ON に設定
3. 「Automatic HTTPS Rewrites」を ON に設定

### 6.3 ファイアウォール設定（オプション）

1. 左メニューから「Security」→「WAF」
2. 必要に応じてルールを追加
   - SQLインジェクション対策
   - XSS対策
   - ボット対策

---

## 第7段階：環境変数の更新

### 7.1 .envファイルの更新

`.env`ファイルを以下のように更新してください：

```env
# アプリケーション設定
APP_NAME="Programmer Aptitude Test"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# CloudflareオプションヘッダーEnable
CLOUDFLARE_ENABLED=true
```

### 7.2 Laravelアプリケーションの確認

```bash
# コンテナ内でアプリケーションをリスタート
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:cache
```

---

## 第8段階：検証

### 8.1 DNSレコード反映確認

```bash
# Aレコード確認
nslookup example.com
# または
dig example.com

# WWWレコード確認
nslookup www.example.com
```

**期待される出力例：**
```
example.com has address XX.XX.XX.XX
www.example.com has address XX.XX.XX.XX
```

### 8.2 HTTPS接続確認

ブラウザで以下のURLにアクセス：
```
https://example.com
https://www.example.com
```

**期待される結果：**
- ページが正常に読み込まれる
- ブラウザのアドレスバーに鍵アイコンが表示される
- 証明書情報が表示される（ブラウザの鍵をクリック）

### 8.3 Cloudflare キャッシュ状態確認

ブラウザの開発者ツール（F12）→ネットワークタブで、以下を確認：
- ヘッダー内に `cf-cache-status: HIT` または `MISS` が表示される
- これはCloudflareがキャッシュを処理していることを示す

---

## トラブルシューティング

### 問題1：「This site can't be reached」エラー

**原因と対策：**
- ネームサーバー反映待機中 → 時間をおいて再試行
- AレコードのIPアドレスが間違っている → Aレコードを確認・修正
- サーバーがダウンしている → サーバー状態を確認

### 問題2：SSL証明書エラー

**原因と対策：**
- Cloudflareの暗号化モードが「Flexible」 → 「Full」に変更
- オリジンサーバーの証明書が無効 → Let's Encryptで取得
- DNS反映が遅延 → 数時間待機

### 問題3：DNSが反映されない

```bash
# DNSキャッシュをクリア（Linux/Mac）
sudo systemctl restart systemd-resolved

# または
sudo dscacheutil -flushcache (Mac)

# DNSサーバーを直接クエリ
dig @1.1.1.1 example.com
dig @8.8.8.8 example.com
```

### 問題4：ページが404 Not Foundになる

**原因と対策：**
- アプリケーションのURL設定が異なる → `.env`の`APP_URL`を確認
- Nginxの設定が間違っている → `docker/nginx.conf`を確認
- 環境変数がキャッシュされている → `php artisan config:cache` を実行

---

## 完了チェックリスト

- [ ] Cloudflareアカウント作成完了
- [ ] ドメインをCloudflareに追加完了
- [ ] ドメイン登録業者でネームサーバーを変更
- [ ] Cloudflareでネームサーバー設定が「Complete」
- [ ] Aレコード（@）を設定
- [ ] Aレコード（www）を設定
- [ ] SSL/TLSを「Full」または「Full (strict)」に設定
- [ ] HTTPSリダイレクトを有効化
- [ ] DNSレコード反映確認
- [ ] HTTPS接続確認
- [ ] .envファイルを更新
- [ ] アプリケーションが正常に起動

---

## 次のステップ

セットアップ完了後は以下の確認を推奨します：

1. **性能監視**: Cloudflareダッシュボードで「Analytics」タブを確認
2. **ログ確認**: アプリケーションログでエラーがないか確認
3. **バックアップ**: DNS設定をバックアップ
4. **ドキュメント更新**: チーム内でドメイン情報を共有

---

## 参考リンク

- [Cloudflare公式ドキュメント](https://developers.cloudflare.com/docs/)
- [DNS設定ガイド](https://developers.cloudflare.com/dns/)
- [SSL/TLS設定ガイド](https://developers.cloudflare.com/ssl/)
