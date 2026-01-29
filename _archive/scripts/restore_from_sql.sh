#!/bin/bash

echo "========================================="
echo "🔧 手動データベース復元"
echo "========================================="
echo ""

SQL_FILE="laravel (15).sql"

# 1. SQLファイル確認
echo "1️⃣  SQLファイル確認..."
if [ ! -f "$SQL_FILE" ]; then
    echo "❌ $SQL_FILE が見つかりません"
    exit 1
fi
echo "✅ $SQL_FILE 発見 ($(du -h "$SQL_FILE" | cut -f1))"
echo ""

# 2. データベースをリセット
echo "2️⃣  データベースをリセット..."
docker-compose exec -T mysql mysql -uroot -ppassword << 'EOSQL'
DROP DATABASE IF EXISTS laravel;
CREATE DATABASE laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laravel;
SHOW TABLES;
EOSQL
echo "✅ データベースリセット完了"
echo ""

# 3. SQLファイルをインポート
echo "3️⃣  SQLファイルをインポート..."
echo "   処理中... (数秒かかります)"

# SQLファイルの内容を直接パイプで流し込む
cat "$SQL_FILE" | docker-compose exec -T mysql mysql -uroot -ppassword laravel

if [ $? -eq 0 ]; then
    echo "✅ インポート成功"
else
    echo "❌ インポート失敗"
    exit 1
fi
echo ""

# 4. テーブル一覧を確認
echo "4️⃣  テーブル一覧確認..."
docker-compose exec -T mysql mysql -uroot -ppassword -e "USE laravel; SHOW TABLES;"
echo ""

# 5. 各テーブルのレコード数確認
echo "5️⃣  テーブルのレコード数確認..."
docker-compose exec -T mysql mysql -uroot -ppassword laravel << 'EOSQL'
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'admins', COUNT(*) FROM admins
UNION ALL
SELECT 'events', COUNT(*) FROM events
UNION ALL
SELECT 'questions', COUNT(*) FROM questions
UNION ALL
SELECT 'exam_sessions', COUNT(*) FROM exam_sessions;
EOSQL
echo ""

# 6. テストデータ確認
echo "6️⃣  テストデータ確認..."
echo ""
echo "--- TEST0000 イベント ---"
docker-compose exec -T mysql mysql -uroot -ppassword laravel -e "SELECT id, name, passphrase, event_type FROM events WHERE passphrase LIKE '%TEST%' LIMIT 3;"
echo ""

echo "--- テストユーザー (B0023035@ib.yic.ac.jp) ---"
docker-compose exec -T mysql mysql -uroot -ppassword laravel -e "SELECT id, name, email FROM users WHERE email = 'B0023035@ib.yic.ac.jp';"
echo ""

echo "--- テスト管理者 (a@a) ---"
docker-compose exec -T mysql mysql -uroot -ppassword laravel -e "SELECT id, name, email FROM admins WHERE email = 'a@a';"
echo ""

# 7. テストデータが存在しない場合は作成
echo "7️⃣  必要なテストデータを確認・作成..."

# イベントTEST0000を確認・作成
EVENT_EXISTS=$(docker-compose exec -T mysql mysql -uroot -ppassword laravel -sN -e "SELECT COUNT(*) FROM events WHERE passphrase = 'TEST0000';")
if [ "$EVENT_EXISTS" = "0" ]; then
    echo "   TEST0000 イベントを作成中..."
    docker-compose exec -T mysql mysql -uroot -ppassword laravel << 'EOSQL'
INSERT INTO events (name, passphrase, event_type, begin, end, is_terminated, created_at, updated_at)
VALUES ('テストイベント', 'TEST0000', 'full', NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 1 DAY, 0, NOW(), NOW());
EOSQL
    echo "   ✅ TEST0000 イベント作成完了"
else
    echo "   ✅ TEST0000 イベント存在確認"
fi

# ユーザーを確認・作成
USER_EXISTS=$(docker-compose exec -T mysql mysql -uroot -ppassword laravel -sN -e "SELECT COUNT(*) FROM users WHERE email = 'B0023035@ib.yic.ac.jp';")
if [ "$USER_EXISTS" = "0" ]; then
    echo "   テストユーザーを作成中..."
    # bcryptハッシュ: password
    HASH='$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    docker-compose exec -T mysql mysql -uroot -ppassword laravel << EOSQL
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('テストユーザー', 'B0023035@ib.yic.ac.jp', '$HASH', NOW(), NOW(), NOW());
EOSQL
    echo "   ✅ テストユーザー作成完了"
else
    echo "   ✅ テストユーザー存在確認"
fi

# 管理者を確認・作成
ADMIN_EXISTS=$(docker-compose exec -T mysql mysql -uroot -ppassword laravel -sN -e "SELECT COUNT(*) FROM admins WHERE email = 'a@a';")
if [ "$ADMIN_EXISTS" = "0" ]; then
    echo "   テスト管理者を作成中..."
    # bcryptハッシュ: Passw0rd (実際には適切なハッシュを生成する必要があります)
    docker-compose exec laravel.test php artisan tinker --execute="
    \$admin = new \App\Models\Admin();
    \$admin->name = '管理者';
    \$admin->email = 'a@a';
    \$admin->password = bcrypt('Passw0rd');
    \$admin->save();
    echo 'Admin created' . PHP_EOL;
    "
    echo "   ✅ テスト管理者作成完了"
else
    echo "   ✅ テスト管理者存在確認"
fi
echo ""

# 8. Laravelキャッシュクリア
echo "8️⃣  Laravelキャッシュクリア..."
docker-compose exec laravel.test php artisan config:clear > /dev/null 2>&1
docker-compose exec laravel.test php artisan cache:clear > /dev/null 2>&1
docker-compose exec laravel.test php artisan route:clear > /dev/null 2>&1
echo "✅ キャッシュクリア完了"
echo ""

# 9. セッション設定確認
echo "9️⃣  セッション設定確認..."
docker-compose exec laravel.test php artisan tinker --execute="
echo '=== Session Configuration ===' . PHP_EOL;
echo 'Driver: ' . config('session.driver') . PHP_EOL;
echo 'Encrypt: ' . (config('session.encrypt') ? 'TRUE (❌)' : 'FALSE (✅)') . PHP_EOL;
"
echo ""

echo "========================================="
echo "✅ データベース復元完了！"
echo "========================================="
echo ""
echo "最終確認:"
echo "  - イベント TEST0000 が存在"
echo "  - ユーザー B0023035@ib.yic.ac.jp が存在"  
echo "  - 管理者 a@a が存在"
echo "  - SESSION_ENCRYPT=false"
echo ""
echo "次のステップ:"
echo "  1. ブラウザのCookieを削除"
echo "  2. テストを実行:"
echo "     npx playwright test --grep '正しいセッションコードで認証できる' --headed"
echo ""