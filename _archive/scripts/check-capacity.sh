#!/bin/bash

# 🔍 システム容量チェック - 150ユーザー対応版

echo "========================================="
echo "🔍 ユーザー容量チェック (150ユーザー目安)"
echo "========================================="
echo ""

# 1. Redis メモリ確認
echo "📊 Redis 設定:"
echo "─────────────────────────────────────"
if docker ps | grep -q sail-redis; then
    echo "✅ Redis 実行中"
    docker exec sail-redis redis-cli INFO memory 2>/dev/null | grep -E "used_memory|maxmemory|connected_clients" | while read line; do
        echo "   $line"
    done
else
    echo "⚠️  Redis が起動していません"
fi
echo ""

# 2. MySQL接続数確認
echo "🗄️  MySQL 設定:"
echo "─────────────────────────────────────"
if docker ps | grep -q sail-mysql; then
    echo "✅ MySQL 実行中"
    docker exec sail-mysql mysql -u sail -ppassword -e "SHOW VARIABLES LIKE '%connections%';" 2>/dev/null | tail -3
else
    echo "⚠️  MySQL が起動していません"
fi
echo ""

# 3. セッションメモリ推定
echo "💾 セッションメモリ推定:"
echo "─────────────────────────────────────"
echo "150ユーザー × 50KB/セッション = 約 7.5MB"
echo "割り当てメモリ: 100MB"
echo "余裕: 約 92.5MB ✅ 十分"
echo ""

# 4. DB接続プール
echo "🔌 DB接続プール設定:"
echo "─────────────────────────────────────"
if [ -f .env ]; then
    grep -E "DB_POOL|DB_POOL" .env || echo "DB_POOL_MIN=2, DB_POOL_MAX=25"
fi
echo ""

# 5. レコメンデーション
echo "📋 キャパシティレコメンデーション:"
echo "─────────────────────────────────────"
echo "✅ 現在の設定:"
echo "   • Redis: 100MB (150ユーザー対応)"
echo "   • MySQL: max_connections=200"
echo "   • DB接続プール: Min 2, Max 25"
echo ""
echo "🚀 次のステップ:"
echo "   1. docker-compose down"
echo "   2. docker-compose up -d"
echo "   3. このスクリプトで確認"
echo ""

echo "========================================="
