#!/bin/bash

echo "========================================="
echo "🚨 緊急修正: セッション問題の解決"
echo "========================================="
echo ""

# 1. Redisの接続確認
echo "1️⃣  Redis接続確認..."
docker-compose exec redis redis-cli ping
if [ $? -ne 0 ]; then
    echo "❌ Redisに接続できません"
    exit 1
fi
echo "✅ Redis接続OK"
echo ""

# 2. Redisに直接書き込みテスト
echo "2️⃣  Redisへの書き込みテスト..."
docker-compose exec redis redis-cli SET test_key "test_value"
docker-compose exec redis redis-cli GET test_key
docker-compose exec redis redis-cli DEL test_key
echo "✅ Redis読み書きOK"
echo ""

# 3. Laravel側からRedis接続テスト
echo "3️⃣  LaravelからRedis接続テスト..."
docker-compose exec laravel.test php artisan tinker --execute="
use Illuminate\Support\Facades\Redis;
try {
    Redis::set('laravel_test', 'hello');
    \$value = Redis::get('laravel_test');
    echo 'Redis Value: ' . \$value . PHP_EOL;
    Redis::del('laravel_test');
    echo '✅ LaravelからRedis接続OK' . PHP_EOL;
} catch (\Exception \$e) {
    echo '❌ エラー: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 4. セッション設定の詳細確認
echo "4️⃣  セッション設定の詳細確認..."
docker-compose exec laravel.test php artisan tinker --execute="
echo 'Session Driver: ' . config('session.driver') . PHP_EOL;
echo 'Session Connection: ' . config('session.connection') . PHP_EOL;
echo 'Session Lifetime: ' . config('session.lifetime') . PHP_EOL;
echo 'Session Cookie: ' . config('session.cookie') . PHP_EOL;
echo 'Redis Host: ' . config('database.redis.default.host') . PHP_EOL;
echo 'Redis Port: ' . config('database.redis.default.port') . PHP_EOL;
echo 'Redis DB: ' . config('database.redis.default.database') . PHP_EOL;
echo 'Redis Prefix: [' . config('database.redis.options.prefix') . ']' . PHP_EOL;
"
echo ""

# 5. セッションファサードのテスト
echo "5️⃣  セッションファサードのテスト..."
docker-compose exec laravel.test php artisan tinker --execute="
use Illuminate\Support\Facades\Session;
try {
    Session::put('test_session_key', 'test_value_' . time());
    Session::save();
    \$sessionId = Session::getId();
    echo 'Session ID: ' . \$sessionId . PHP_EOL;
    \$value = Session::get('test_session_key');
    echo 'Session Value: ' . \$value . PHP_EOL;
    echo '✅ セッション書き込みOK' . PHP_EOL;
} catch (\Exception \$e) {
    echo '❌ セッションエラー: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

# 6. Redisにセッションキーがあるか確認
echo "6️⃣  Redisにセッションキーが保存されているか確認..."
docker-compose exec redis redis-cli KEYS "*"
echo ""

# 7. キャッシュとコンフィグを完全にクリア
echo "7️⃣  キャッシュとコンフィグを完全にクリア..."
docker-compose exec laravel.test php artisan optimize:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan route:clear
docker-compose exec laravel.test php artisan view:clear
echo "✅ クリア完了"
echo ""

# 8. Composer autoload を再生成
echo "8️⃣  Composer autoload 再生成..."
docker-compose exec laravel.test composer dump-autoload
echo "✅ Autoload 再生成完了"
echo ""

# 9. 再起動
echo "9️⃣  コンテナ再起動..."
docker-compose restart laravel.test redis
echo "✅ 再起動完了"
echo ""

# 10. 最終確認
echo "🔟  最終確認..."
sleep 3
docker-compose exec laravel.test php artisan tinker --execute="
\$token = csrf_token();
echo 'CSRF Token: ' . \$token . PHP_EOL;
echo 'Token Length: ' . strlen(\$token) . PHP_EOL;
if (strlen(\$token) > 0) {
    echo '✅ CSRFトークン生成成功！' . PHP_EOL;
} else {
    echo '❌ まだCSRFトークンが生成されていません' . PHP_EOL;
}
"
echo ""

echo "========================================="
echo "診断完了"
echo "========================================="