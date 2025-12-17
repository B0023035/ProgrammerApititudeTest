#!/bin/bash

echo "========================================="
echo "🐳 Dockerコンテナ内からWebテスト"
echo "========================================="
echo ""

echo "1️⃣  コンテナ内部からlocalhostにアクセス..."
echo ""

# Laravelコンテナ内からcurl実行（プロキシの影響を受けない）
docker-compose exec laravel.test curl -s http://localhost/debug-csrf-web | python3 -m json.tool

echo ""
echo ""

echo "2️⃣  セッションが保存されているか確認..."
echo ""
docker-compose exec redis redis-cli KEYS "*"

echo ""
echo ""

echo "3️⃣  各キーのTTL（有効期限）確認..."
echo ""
docker-compose exec redis redis-cli --raw KEYS "*" | while read key; do
    if [ ! -z "$key" ]; then
        echo "キー: $key"
        docker-compose exec redis redis-cli TTL "$key"
        echo ""
    fi
done

echo "========================================="
echo "✅ 診断完了"
echo "========================================="
echo ""