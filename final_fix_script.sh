#!/bin/bash

echo "========================================="
echo "🔧 419エラー完全修正"
echo "========================================="
echo ""

echo "問題: セッションが暗号化されているため、Laravelがセッションを読み取れない"
echo ""

# 1. .env を確認
echo "1️⃣  現在のSESSION_ENCRYPT設定を確認..."
grep SESSION_ENCRYPT .env || echo "SESSION_ENCRYPT=false" >> .env
echo ""

# 2. SESSION_ENCRYPTをfalseに設定
echo "2️⃣  SESSION_ENCRYPT=false に設定中..."
if grep -q "SESSION_ENCRYPT=" .env; then
    sed -i 's/SESSION_ENCRYPT=.*/SESSION_ENCRYPT=false/' .env
else
    echo "SESSION_ENCRYPT=false" >> .env
fi
echo "✅ SESSION_ENCRYPT=false に設定完了"
echo ""

# 3. 設定確認
echo "3️⃣  .env の設定を確認..."
echo "SESSION_DRIVER=$(grep SESSION_DRIVER .env)"
echo "SESSION_ENCRYPT=$(grep SESSION_ENCRYPT .env)"
echo "SESSION_CONNECTION=$(grep SESSION_CONNECTION .env)"
echo "REDIS_PREFIX=$(grep REDIS_PREFIX .env)"
echo ""

# 4. 全てのキャッシュとセッションをクリア
echo "4️⃣  全てのキャッシュとセッションをクリア..."
docker-compose exec laravel.test php artisan optimize:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan view:clear
echo "✅ クリア完了"
echo ""

# 5. Redisをフラッシュ（暗号化されたセッションを削除）
echo "5️⃣  Redisをフラッシュ（重要！暗号化されたセッションを削除）..."
docker-compose exec redis redis-cli FLUSHALL
echo "✅ Redisフラッシュ完了"
echo ""

# 6. コンテナを再起動
echo "6️⃣  コンテナを再起動..."
docker-compose restart laravel.test redis
sleep 3
echo "✅ 再起動完了"
echo ""

# 7. 設定確認
echo "7️⃣  設定を確認..."
docker-compose exec laravel.test php artisan tinker --execute="
echo 'Session Driver: ' . config('session.driver') . PHP_EOL;
echo 'Session Encrypt: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
echo 'Session Connection: ' . config('session.connection') . PHP_EOL;
echo 'Redis Prefix: [' . config('database.redis.options.prefix') . ']' . PHP_EOL;
"
echo ""

# 8. Webテスト
echo "8️⃣  Webブラウザからセッションをテスト..."
docker-compose exec laravel.test curl -s http://localhost/debug-csrf-web | python3 -m json.tool
echo ""
echo ""

# 9. Playwrightテストを実行
echo "9️⃣  Playwrightテストを実行..."
echo ""
echo "以下のコマンドでテストを実行してください:"
echo "  npx playwright test --grep '練習を完了できる' --headed"
echo ""

echo "========================================="
echo "✅ 修正完了！"
echo "========================================="
echo ""
echo "次のステップ:"
echo "1. ブラウザのCookieを全て削除してください"
echo "2. Playwrightテストを再実行してください"
echo "   npx playwright test --grep '練習を完了できる'"
echo ""
echo "期待される結果:"
echo "  - Status: 200 (419ではない)"
echo "  - セッションCookieが暗号化されていない（40文字の英数字）"
echo "  - x-xsrf-tokenが送信されない"
echo ""