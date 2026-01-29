#!/bin/bash
# ===========================================
# ProgrammerAptitudeTest 停止スクリプト
# ===========================================

echo "🛑 ProgrammerAptitudeTest を停止します..."

cd ~/ProgrammerAptitudeTest

# 1. Cloudflare Tunnel停止
echo "🌐 Cloudflare Tunnelを停止中..."
pkill cloudflared 2>/dev/null || true

# 2. Dockerコンテナ停止
echo "📦 Dockerコンテナを停止中..."
docker compose -f docker-compose.prod-test.yml down

echo ""
echo "✅ 停止完了"
