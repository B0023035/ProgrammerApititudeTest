# インストールマニュアル

## 前提条件

### 必要なソフトウェア
- Docker 20.10以上
- Docker Compose 2.0以上
- Git

### 推奨スペック
- CPU: 2コア以上
- メモリ: 4GB以上
- ストレージ: 20GB以上

---

## 1. クイックスタート（Docker）

### 1.1 リポジトリのクローン
```bash
git clone https://github.com/your-repo/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest
```

### 1.2 環境変数ファイルの作成
```bash
cp .env.example .env
```

### 1.3 環境変数の設定
`.env`ファイルを編集:
```env
APP_NAME="Programmer Aptitude Test"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_DRIVER=redis
```

### 1.4 Dockerコンテナの起動
```bash
# 本番環境
docker compose -f docker-compose.prod.yml up -d --build

# または開発環境
docker compose up -d --build
```

### 1.5 アプリケーションキーの生成
```bash
docker compose exec app php artisan key:generate
```

### 1.6 データベースマイグレーション
```bash
docker compose exec app php artisan migrate --seed
```

### 1.7 フロントエンドビルド
```bash
docker compose exec app npm install
docker compose exec app npm run build
```

---

## 2. 手動インストール（非Docker）

### 2.1 必要なソフトウェアのインストール
```bash
# PHP 8.2とエクステンション
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-redis

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (20.x)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs

# MySQL 8.0
sudo apt install mysql-server

# Redis
sudo apt install redis-server

# Nginx
sudo apt install nginx
```

### 2.2 プロジェクトセットアップ
```bash
cd /var/www
git clone https://github.com/your-repo/ProgrammerAptitudeTest.git
cd ProgrammerAptitudeTest

# Composerパッケージインストール
composer install --optimize-autoloader --no-dev

# NPMパッケージインストール
npm install
npm run build

# 権限設定
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 2.3 Nginx設定
`/etc/nginx/sites-available/programmer-test`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/ProgrammerAptitudeTest/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/programmer-test /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 3. データベース初期設定

### 3.1 データベース作成
```bash
mysql -u root -p
```

```sql
CREATE DATABASE laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laravel'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON laravel.* TO 'laravel'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.2 マイグレーションとシード
```bash
php artisan migrate --seed
```

---

## 4. 初期管理者アカウント

シード実行後、以下のアカウントが作成されます:

| 種別 | メールアドレス | パスワード |
|------|--------------|-----------|
| 管理者 | admin@example.com | password |
| テストユーザー | test@example.com | password |

**⚠️ 本番環境では必ずパスワードを変更してください**

---

## 5. SSL証明書設定（Let's Encrypt）

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

---

## 6. Cloudflare設定

1. Cloudflareにドメインを追加
2. DNSレコードを設定（A/AAAAレコード）
3. SSL/TLS設定を「Full (strict)」に設定
4. `.env`に以下を追加:
```env
TRUSTED_PROXIES=*
```

---

## 7. 動作確認

```bash
# アプリケーションの状態確認
php artisan about

# ルート一覧確認
php artisan route:list

# キャッシュクリア
php artisan optimize:clear
```

ブラウザで `https://your-domain.com` にアクセスして動作確認。

---

## トラブルシューティング

### 500エラーが発生する
```bash
# ログを確認
tail -f storage/logs/laravel.log

# 権限を再設定
sudo chown -R www-data:www-data storage bootstrap/cache
```

### データベース接続エラー
```bash
# 接続テスト
php artisan db:monitor

# .envのDB設定を確認
```

### Redisに接続できない
```bash
# Redisの状態確認
sudo systemctl status redis

# 接続テスト
redis-cli ping
```
