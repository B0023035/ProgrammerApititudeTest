#!/bin/bash

echo "========================================="
echo "🔥 完全クリーンアップ（Cookie強制削除）"
echo "========================================="
echo ""

# 1. Redisを完全にクリア
echo "1️⃣  Redis完全クリア..."
docker-compose exec redis redis-cli FLUSHALL
echo "✅ Redisクリア完了"
echo ""

# 2. Laravelのすべてのキャッシュをクリア
echo "2️⃣  Laravelキャッシュクリア..."
docker-compose exec laravel.test php artisan optimize:clear
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan route:clear
docker-compose exec laravel.test php artisan view:clear
echo "✅ キャッシュクリア完了"
echo ""

# 3. Laravelコンテナを再起動
echo "3️⃣  Laravelコンテナ再起動..."
docker-compose restart laravel.test
sleep 5
echo "✅ 再起動完了"
echo ""

# 4. 設定確認
echo "4️⃣  セッション設定確認..."
docker-compose exec laravel.test php artisan tinker --execute="
echo '=== Session Configuration ===' . PHP_EOL;
echo 'Driver: ' . config('session.driver') . PHP_EOL;
echo 'Encrypt: ' . (config('session.encrypt') ? 'TRUE (❌ 問題あり!)' : 'FALSE (✅ OK)') . PHP_EOL;
echo 'Connection: ' . config('session.connection') . PHP_EOL;
echo 'Cookie: ' . config('session.cookie') . PHP_EOL;
"
echo ""

# 5. 新しいセッションをテスト
echo "5️⃣  新しいセッションテスト..."
RESPONSE=$(docker-compose exec laravel.test curl -s -c /tmp/cookies.txt http://localhost/debug-csrf-web)
echo "$RESPONSE" | python3 -m json.tool
echo ""

# セッションIDを確認
SESSION_ID=$(echo "$RESPONSE" | python3 -c "import sys, json; print(json.load(sys.stdin)['session_id'])")
echo "📋 新しいセッションID: $SESSION_ID"
echo "📏 長さ: ${#SESSION_ID} 文字"

if [ ${#SESSION_ID} -eq 40 ]; then
    echo "✅ セッションIDは40文字の平文です（正常）"
else
    echo "❌ セッションIDが暗号化されています（異常）"
fi
echo ""

echo "========================================="
echo "✅ クリーンアップ完了！"
echo "========================================="
echo ""
echo "🚨 重要: Playwright/ブラウザのCookieを削除してください"
echo ""
echo "方法1: Playwrightのストレージをクリア"
echo "  rm -rf tests/.auth"
echo "  rm -rf playwright/.auth"
echo ""
echo "方法2: テストに --headed オプションをつけて手動でCookieを削除"
echo "  1. テストを --headed で実行"
echo "  2. F12でDevToolsを開く"
echo "  3. Application タブ → Cookies → localhost を右クリック → Clear"
echo ""
echo "方法3: テストスクリプトを修正してCookie削除を追加"
echo ""
echo "次のステップ:"
echo "  1. 上記のいずれかの方法でCookieを削除"
echo "  2. テストを実行:"
echo "     npx playwright test --grep '練習を完了できる' --headed"
echo ""