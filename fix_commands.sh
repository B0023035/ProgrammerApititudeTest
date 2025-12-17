#!/bin/bash

# ========================================
# CSRF 419エラー修正スクリプト
# ========================================

echo "========================================="
echo "🔧 CSRF 419エラー修正開始"
echo "========================================="
echo ""

# 1. 設定ファイルをクリア
echo "1️⃣  設定ファイルをクリア中..."
docker-compose exec laravel.test php artisan config:clear
docker-compose exec laravel.test php artisan cache:clear
docker-compose exec laravel.test php artisan view:clear
echo "✅ 設定クリア完了"
echo ""

# 2. Redisをフラッシュ（既存のセッションをすべて削除）
echo "2️⃣  Redisをフラッシュ中..."
docker-compose exec redis redis-cli FLUSHALL
echo "✅ Redisフラッシュ完了"
echo ""

# 3. コンテナを再起動
echo "3️⃣  コンテナを再起動中..."
docker-compose restart laravel.test
echo "✅ 再起動完了"
echo ""

# 4. 設定確認
echo "4️⃣  設定を確認中..."
echo ""
echo "セッションドライバー:"
docker-compose exec laravel.test php artisan tinker --execute="echo config('session.driver');"
echo ""
echo "セッション接続:"
docker-compose exec laravel.test php artisan tinker --execute="echo config('session.connection');"
echo ""
echo "Redisプレフィックス:"
docker-compose exec laravel.test php artisan tinker --execute="echo config('database.redis.options.prefix');"
echo ""

# 5. Redisキー確認
echo "5️⃣  Redisに保存されているキーを確認..."
docker-compose exec redis redis-cli KEYS "*"
echo ""

echo "========================================="
echo "✅ 修正完了！"
echo "========================================="
echo ""
echo "次のステップ:"
echo "1. ブラウザのCookieをクリアしてください"
echo "2. アプリケーションに再度アクセスしてください"
echo "3. 練習問題を最後まで実行してください"
echo "4. 419エラーが出ないか確認してください"
echo ""
echo "問題が続く場合は、ログを確認してください:"
echo "  docker-compose logs -f laravel.test"
echo ""

echo "========================================="
echo "🔍 追加診断（419エラーが続く場合）"
echo "========================================="
echo ""

# 1. セッションが正しく作成されているか確認
echo "1️⃣  セッション作成テスト..."
echo ""

# テストページにアクセス
echo "デバッグページでセッションをテスト:"
echo "curl http://localhost/debug-session-simple"
echo ""
curl -s http://localhost/debug-session-simple | jq '.'
echo ""

# 2. Redisにセッションが保存されているか確認
echo "2️⃣  Redis内のセッションキー確認..."
sleep 2  # セッション作成を待つ
docker-compose exec redis redis-cli KEYS "*"
echo ""

# 3. セッションの詳細情報
echo "3️⃣  セッション詳細情報..."
curl -s http://localhost/debug-session-detailed | jq '.'
echo ""

# 4. Laravel ログの最後の部分を表示
echo "4️⃣  Laravel ログの最新部分..."
docker-compose exec laravel.test tail -n 50 storage/logs/laravel.log
echo ""

# 5. CSRF トークンの確認
echo "5️⃣  CSRFトークン確認..."
docker-compose exec laravel.test php artisan tinker --execute="
\$token = csrf_token();
echo 'CSRF Token: ' . \$token . PHP_EOL;
echo 'Token Length: ' . strlen(\$token) . PHP_EOL;
"
echo ""

echo "========================================="
echo "診断完了"
echo "========================================="
echo ""
echo "上記の結果を確認してください:"
echo "- セッションIDが表示されているか"
echo "- Redisにキーが保存されているか"
echo "- CSRFトークンが生成されているか"
echo ""