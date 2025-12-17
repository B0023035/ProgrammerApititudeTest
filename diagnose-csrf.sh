#!/bin/bash

echo "========================================="
echo "🔍 CSRF トークンエラー診断"
echo "========================================="
echo ""

echo "1️⃣ Redis 接続確認:"
docker-compose exec redis redis-cli ping
echo ""

echo "2️⃣ Redis セッションキー確認:"
docker-compose exec redis redis-cli KEYS "*session*"
echo ""

echo "3️⃣ Laravel から Redis 接続テスト:"
docker-compose exec laravel.test php -r "
try {
    echo 'Redis接続テスト...' . PHP_EOL;
    \$redis = new Redis();
    \$redis->connect('redis', 6379);
    echo '✅ Redis接続成功' . PHP_EOL;
    
    // テスト書き込み
    \$redis->set('test_key', 'test_value');
    \$value = \$redis->get('test_key');
    echo '✅ 読み書きテスト: ' . \$value . PHP_EOL;
    
    // セッションキー確認
    \$keys = \$redis->keys('*');
    echo 'Redis内のキー数: ' . count(\$keys) . PHP_EOL;
    
} catch (Exception \$e) {
    echo '❌ エラー: ' . \$e->getMessage() . PHP_EOL;
}
"
echo ""

echo "4️⃣ セッション設定確認:"
docker-compose exec laravel.test php artisan tinker --execute="
echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
echo 'CACHE_STORE: ' . config('cache.default') . PHP_EOL;
echo 'REDIS_HOST: ' . config('database.redis.default.host') . PHP_EOL;
echo 'APP_URL: ' . config('app.url') . PHP_EOL;
echo 'SESSION_DOMAIN: ' . config('session.domain') . PHP_EOL;
echo 'SESSION_SECURE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
"
echo ""

echo "5️⃣ Storage 権限確認:"
docker-compose exec laravel.test ls -la storage/framework/ | grep -E "sessions|views|cache"
echo ""

echo "========================================="
echo "✅ 診断完了"
echo ""
echo "次のステップ:"
echo "1. ブラウザで http://localhost にアクセス"
echo "2. F12 開発者ツール → Application → Cookies"
echo "3. laravel_session クッキーが作成されているか確認"
echo "========================================="