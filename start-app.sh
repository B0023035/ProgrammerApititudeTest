#!/bin/bash
# ===========================================
# ProgrammerAptitudeTest 起動スクリプト
# ===========================================

set -e

echo "🚀 ProgrammerAptitudeTest を起動します..."

cd ~/ProgrammerAptitudeTest

# 1. Dockerコンテナ起動
echo "📦 Dockerコンテナを起動中..."
docker compose -f docker-compose.prod-test.yml up -d

# 2. コンテナが起動するまで待機
echo "⏳ コンテナの起動を待機中..."
sleep 10

# 3. ヘルスチェック
echo "🔍 ローカル動作確認..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ || echo "000")
if [ "$HTTP_CODE" != "200" ]; then
    echo "❌ ローカルアクセス失敗 (HTTP $HTTP_CODE)"
    echo "ログを確認してください: docker compose -f docker-compose.prod-test.yml logs prod-app"
    exit 1
fi
echo "✅ ローカルアクセス成功"

# 4. Cloudflare Tunnel起動
echo "🌐 Cloudflare Tunnelを起動中..."
pkill cloudflared 2>/dev/null || true
sleep 1
nohup cloudflared tunnel run minmi-tunnel > /tmp/cloudflared.log 2>&1 &
sleep 5

# 5. 外部アクセス確認
echo "🔍 外部アクセス確認中..."
HTTPS_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://aws-sample-minmi.click/ || echo "000")
if [ "$HTTPS_CODE" != "200" ]; then
    echo "⚠️  外部アクセス確認失敗 (HTTP $HTTPS_CODE)"
    echo "数秒待ってから再度確認してください"
else
    echo "✅ 外部アクセス成功"
fi

echo ""
echo "=========================================="
echo "🎉 起動完了！"
echo ""
echo "ローカル:  http://localhost/"
echo "外部公開:  https://aws-sample-minmi.click/"
echo "phpMyAdmin: http://localhost:8080/"
echo "=========================================="
