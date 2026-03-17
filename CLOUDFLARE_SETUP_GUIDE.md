# Cloudflare Tunnel 新規アカウント設定ガイド

## 仮の設定値（実際の値に置き換えてください）
- ドメイン: `example-app.com`
- トンネル名: `my-app-tunnel`
- トンネルID: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`（作成後に確認）

---

## Part 1: Cloudflareダッシュボードでの準備

### 1.1 ドメイン追加
1. https://dash.cloudflare.com にログイン
2. 「Add a Site」をクリック
3. ドメイン名を入力（例: `example-app.com`）
4. プランを選択（Free でOK）
5. ネームサーバーを変更（ドメインレジストラ側で設定）

### 1.2 Origin Certificate 作成（SSL証明書）
1. ダッシュボード → SSL/TLS → Origin Server
2. 「Create Certificate」クリック
3. 設定:
   - Private key type: RSA (2048)
   - Hostnames: `example-app.com`, `*.example-app.com`
   - Certificate Validity: 15 years
4. 「Create」クリック
5. 表示された証明書をコピー:
   - Origin Certificate → `fullchain.pem` として保存
   - Private Key → `privkey.pem` として保存

---

## Part 2: ターミナルでの設定

### 2.1 既存の認証情報削除
```bash
pkill cloudflared 2>/dev/null
rm -rf ~/.cloudflared
```

### 2.2 新しいアカウントでログイン
```bash
cloudflared tunnel login
```
→ ブラウザが開いたら新しいアカウントでログインし、ドメインを選択

### 2.3 トンネル作成
```bash
cloudflared tunnel create my-app-tunnel
```

出力例:
```
Tunnel credentials written to /home/miaru/.cloudflared/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.json
Created tunnel my-app-tunnel with id xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

### 2.4 トンネルID確認
```bash
cloudflared tunnel list
```

出力例:
```
ID                                   NAME           CREATED              
xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx my-app-tunnel  2026-03-17T10:00:00Z
```

### 2.5 設定ファイル作成

**重要: `TUNNEL_ID` を実際のIDに置き換えてください**

```bash
# トンネルIDを変数に設定（実際のIDに置き換え）
TUNNEL_ID="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
DOMAIN="example-app.com"

# 設定ファイル作成
cat > ~/.cloudflared/config.yml << EOF
tunnel: ${TUNNEL_ID}
credentials-file: /home/miaru/.cloudflared/${TUNNEL_ID}.json

ingress:
  - hostname: ${DOMAIN}
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
  - hostname: www.${DOMAIN}
    service: https://localhost:443
    originRequest:
      noTLSVerify: true
  - service: http_status:404
EOF

echo "設定ファイル作成完了"
cat ~/.cloudflared/config.yml
```

### 2.6 DNSルート設定
```bash
cloudflared tunnel route dns my-app-tunnel example-app.com
cloudflared tunnel route dns my-app-tunnel www.example-app.com
```

### 2.7 SSL証明書配置

ダッシュボードからダウンロードした証明書をプロジェクトに配置:

```bash
# ディレクトリ作成
mkdir -p ~/ProgrammerApititudeTest/certbot/conf/live/example-app.com

# 証明書ファイルを作成（ダッシュボードからコピーした内容を貼り付け）
nano ~/ProgrammerApititudeTest/certbot/conf/live/example-app.com/fullchain.pem
nano ~/ProgrammerApititudeTest/certbot/conf/live/example-app.com/privkey.pem
```

または直接作成:
```bash
cat > ~/ProgrammerApititudeTest/certbot/conf/live/example-app.com/fullchain.pem << 'EOF'
-----BEGIN CERTIFICATE-----
（ダッシュボードからコピーした証明書をここに貼り付け）
-----END CERTIFICATE-----
EOF

cat > ~/ProgrammerApititudeTest/certbot/conf/live/example-app.com/privkey.pem << 'EOF'
-----BEGIN PRIVATE KEY-----
（ダッシュボードからコピーした秘密鍵をここに貼り付け）
-----END PRIVATE KEY-----
EOF
```

---

## Part 3: Docker設定の更新

### 3.1 docker-compose.production.yml の修正

`certbot/conf` のマウント先がドメイン名と一致していることを確認:

```yaml
volumes:
  - ./certbot/conf:/etc/letsencrypt:ro
```

### 3.2 Nginx設定の更新（必要な場合）

docker/default.conf でドメイン名を変更:

```bash
# 現在の設定を確認
grep -n "server_name" docker/default.conf

# ドメイン名を置換（例）
sed -i 's/aws-sample-minmi.click/example-app.com/g' docker/default.conf
sed -i 's/aws-sample-minmi.click/example-app.com/g' docker/default-https.conf
sed -i 's/aws-sample-minmi.click/example-app.com/g' docker/default-cloudflare.conf
```

### 3.3 start.sh の更新（必要な場合）
```bash
sed -i 's/aws-sample-minmi.click/example-app.com/g' docker/start.sh
```

---

## Part 4: システム起動

### 4.1 Dockerコンテナ再ビルド＆起動
```bash
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down -v
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d
```

### 4.2 起動確認
```bash
docker logs programmer-test-app --tail 30
```

### 4.3 Cloudflareトンネル起動
```bash
# フォアグラウンド（ログ確認用）
cloudflared tunnel run my-app-tunnel

# バックグラウンド（本番運用）
nohup cloudflared tunnel run my-app-tunnel > /tmp/cloudflared.log 2>&1 &
```

---

## Part 5: 運用コマンド

### 起動
```bash
# 1. Dockerコンテナ起動
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml up -d

# 2. Cloudflareトンネル起動
nohup cloudflared tunnel run my-app-tunnel > /tmp/cloudflared.log 2>&1 &
```

### 停止
```bash
# 1. Cloudflareトンネル停止
pkill cloudflared

# 2. Dockerコンテナ停止
cd ~/ProgrammerApititudeTest
docker compose -f docker-compose.production.yml down
```

### 状態確認
```bash
# Docker
docker ps
docker logs programmer-test-app --tail 20

# Cloudflare
cloudflared tunnel list
cat /tmp/cloudflared.log
```

---

## チェックリスト

- [ ] Cloudflareにドメイン追加完了
- [ ] ネームサーバー変更完了（DNS伝播に最大48時間）
- [ ] Origin Certificate作成完了
- [ ] `cloudflared tunnel login` 完了
- [ ] トンネル作成完了
- [ ] config.yml 作成完了
- [ ] DNSルート設定完了
- [ ] SSL証明書をプロジェクトに配置
- [ ] Dockerコンテナ起動確認
- [ ] Cloudflareトンネル起動確認
- [ ] ブラウザでアクセス確認

---

## トラブルシューティング

### 「Bad gateway」エラー
```bash
# Dockerコンテナが起動しているか確認
docker ps

# コンテナログ確認
docker logs programmer-test-app
```

### 「SSL certificate problem」
```bash
# 証明書が正しく配置されているか確認
ls -la certbot/conf/live/example-app.com/

# コンテナ内で確認
docker exec programmer-test-app ls -la /etc/letsencrypt/live/example-app.com/
```

### DNSが解決しない
```bash
# DNS確認
nslookup example-app.com
dig example-app.com
```

---

*作成日: 2026-03-17*
