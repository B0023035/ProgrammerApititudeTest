#!/bin/bash

# 🔍 ネットワークアクセス診断スクリプト
# 用途: Docker コンテナへのネットワークアクセスがうまくいかない時のデバッグ
# 実行: bash diagnose-network.sh

set -e

echo "========================================="
echo "🔍 ネットワークアクセス診断"
echo "========================================="
echo ""

# ホストIP取得
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    HOST_IP=$(hostname -I | awk '{print $1}')
elif [[ "$OSTYPE" == "darwin"* ]]; then
    HOST_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)
else
    HOST_IP=$(hostname -I | awk '{print $1}')
fi

echo "📍 ホストマシンIP: $HOST_IP"
echo ""

# 1. Docker コンテナ起動状態
echo "========================================="
echo "1️⃣  Docker コンテナ状態確認"
echo "========================================="
echo ""

if ! docker ps > /dev/null 2>&1; then
    echo "❌ Docker が起動していません"
    exit 1
fi

CONTAINER_ID=$(docker-compose ps -q laravel.test 2>/dev/null || echo "")

if [ -z "$CONTAINER_ID" ]; then
    echo "❌ Laravel コンテナが見つかりません"
    echo ""
    echo "起動しているコンテナ:"
    docker-compose ps
    exit 1
fi

echo "✅ Laravel コンテナが起動中: $CONTAINER_ID"
echo ""

# 2. コンテナ内での接続テスト
echo "========================================="
echo "2️⃣  コンテナ内での接続テスト"
echo "========================================="
echo ""

if docker-compose exec app curl -s http://localhost/ > /dev/null 2>&1; then
    echo "✅ コンテナ内 (localhost) からアクセス可能"
else
    echo "❌ コンテナ内からもアクセスできません"
    echo "   Laravel サーバーが起動していない可能性があります"
fi

echo ""

# 3. コンテナ内での curl詳細テスト
echo "========================================="
echo "3️⃣  コンテナ内での詳細なレスポンス確認"
echo "========================================="
echo ""

echo "コンテナからの HTTP レスポンス:"
docker-compose exec app curl -v http://localhost/ 2>&1 | head -20 || echo "エラー"

echo ""

# 4. ホストマシンからのポートバインド確認
echo "========================================="
echo "4️⃣  ホストマシンのポート 80 確認"
echo "========================================="
echo ""

# Linux
if command -v netstat &> /dev/null; then
    echo "ポート 80 のバインド状態:"
    sudo netstat -tulpn 2>/dev/null | grep :80 || echo "バインドされていません"
    echo ""
fi

# lsof
if command -v lsof &> /dev/null; then
    echo "ポート 80 を使用しているプロセス:"
    sudo lsof -i :80 2>/dev/null || echo "情報なし"
    echo ""
fi

# 5. ホストからの接続テスト
echo "========================================="
echo "5️⃣  ホストマシンからの接続テスト"
echo "========================================="
echo ""

echo "localhost からのテスト:"
if curl -s -m 3 http://localhost/ > /dev/null 2>&1; then
    echo "✅ localhost で接続可能"
else
    echo "⚠️  localhost で接続できません"
fi

echo ""

echo "$HOST_IP からのテスト:"
if timeout 3 bash -c "echo > /dev/tcp/127.0.0.1/80" 2>/dev/null; then
    echo "✅ 127.0.0.1:80 に接続可能"
else
    echo "❌ 127.0.0.1:80 に接続できません"
fi

echo ""

# 6. Docker ネットワーク確認
echo "========================================="
echo "6️⃣  Docker ネットワーク確認"
echo "========================================="
echo ""

echo "コンテナの IP アドレス:"
CONTAINER_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $CONTAINER_ID 2>/dev/null || echo "不明")
echo "  $CONTAINER_IP"

echo ""

echo "Docker ネットワーク一覧:"
docker network ls

echo ""

# 7. docker-compose.yml の ポート設定確認
echo "========================================="
echo "7️⃣  docker-compose.yml のポート設定確認"
echo "========================================="
echo ""

echo "ports 設定:"
docker-compose config | grep -A 2 "ports:" | head -10 || echo "設定が見つかりません"

echo ""

# 8. ファイアウォール確認
echo "========================================="
echo "8️⃣  ファイアウォール確認"
echo "========================================="
echo ""

if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    if command -v ufw &> /dev/null; then
        UFW_STATUS=$(sudo ufw status 2>/dev/null | head -1 || echo "不明")
        echo "UFW ステータス: $UFW_STATUS"
        
        if [[ $UFW_STATUS == *"active"* ]]; then
            echo ""
            echo "⚠️  UFW ファイアウォールが有効です"
            echo "以下のコマンドでポート 80 を許可してください:"
            echo "  sudo ufw allow 80/tcp"
            echo "  sudo ufw allow 443/tcp"
        fi
    fi
    echo ""
fi

# 9. 推奨アクション
echo "========================================="
echo "✅ 診断完了"
echo "========================================="
echo ""

echo "推奨アクション:"
echo ""
echo "1. 上記の結果を確認"
echo "2. 問題に応じて修正:"
echo ""
echo "   問題: コンテナ内からもアクセスできない"
echo "   → docker-compose logs -f app"
echo "     でログを確認"
echo ""
echo "   問題: ファイアウォールが有効"
echo "   → sudo ufw allow 80/tcp"
echo "     でポート 80 を許可"
echo ""
echo "   問題: ポートがバインドされていない"
echo "   → docker-compose down && docker-compose up -d"
echo "     で再起動"
echo ""

# 10. curl での直接テスト
echo "========================================="
echo "🌐 直接テスト"
echo "========================================="
echo ""

echo "HTTP レスポンス確認:"
echo ""
echo "localhost:80"
curl -v http://localhost/ 2>&1 | head -30 || echo "エラー"

echo ""
echo ""
echo "外部IP (0.0.0.0):80"
# nc または /dev/tcp を使って接続テスト
if timeout 3 bash -c "echo -e 'GET / HTTP/1.1\r\nHost: localhost\r\n\r\n' | nc -w 1 0.0.0.0 80" 2>/dev/null; then
    echo "✅ 接続成功"
else
    echo "⚠️  接続テスト結果:"
    timeout 3 bash -c "echo > /dev/tcp/0.0.0.0/80" && echo "✅ ポート開放" || echo "❌ ポート閉鎖"
fi

echo ""
echo "完了！"
