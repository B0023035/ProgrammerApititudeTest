#!/bin/bash

echo "========================================="
echo "🌐 Webブラウザでのセッションテスト"
echo "========================================="
echo ""

echo "1️⃣  CSRFトークンとセッションのテスト..."
echo ""
curl -v http://localhost/debug-csrf-web 2>&1 | head -50
echo ""
echo ""

echo "2️⃣  上記の結果を確認:"
echo "  - csrf_token が40文字の文字列であること"
echo "  - csrf_token_length が 40 であること"
echo "  - session_id が設定されていること"
echo ""

echo "3️⃣  実際のブラウザでテスト:"
echo "  http://localhost/debug-csrf-web"
echo "  をブラウザで開いて確認してください"
echo ""

echo "4️⃣  Redisのキー確認:"
docker-compose exec redis redis-cli KEYS "*"
echo ""

echo "========================================="
echo "次のステップ"
echo "========================================="
echo ""
echo "1. 上記のcurlコマンドでCSRFトークンが生成されていれば成功"
echo "2. ブラウザで http://localhost/ にアクセスして実際のアプリをテスト"
echo "3. 練習問題を最後まで実行して419エラーが出ないか確認"
echo ""