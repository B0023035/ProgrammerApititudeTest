#!/bin/bash

# 🌐 ネットワークアクセステストスクリプト
# 用途: 他のPCからのアクセステスト自動化
# 実行: bash test-network-access.sh

set -e

echo "========================================="
echo "🌐 ネットワークアクセステスト"
echo "========================================="
echo ""

# ステップ1: ホストマシンのIP取得
echo "📍 ステップ1: ホストマシンのIPアドレスを取得中..."
echo ""

# OSを判定
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    HOST_IP=$(hostname -I | awk '{print $1}')
elif [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    HOST_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)
else
    # Windows (WSL) または不明
    HOST_IP=$(hostname -I | awk '{print $1}')
fi

if [ -z "$HOST_IP" ]; then
    echo "❌ IPアドレスが取得できません。手動で指定してください。"
    read -p "IPアドレスを入力: " HOST_IP
fi

echo "✅ ホストマシンIP: $HOST_IP"
echo ""

# ステップ2: Docker コンテナ起動確認
echo "🐳 ステップ2: Docker コンテナを確認中..."
echo ""

if ! docker ps > /dev/null 2>&1; then
    echo "❌ Docker が起動していません。"
    echo "   docker-compose up -d を実行してください。"
    exit 1
fi

if ! docker-compose ps | grep -q laravel.test; then
    echo "❌ Laravel コンテナが起動していません。"
    echo "   docker-compose up -d を実行してください。"
    exit 1
fi

echo "✅ Docker コンテナが起動中"
echo ""

# ステップ3: コンテナ内のWebサーバーを確認
echo "🌐 ステップ3: コンテナ内のWebサーバーを確認中..."
echo ""

if docker-compose exec app curl -s http://localhost/health > /dev/null 2>&1; then
    echo "✅ Webサーバーが応答中"
else
    echo "⚠️  Webサーバーの確認ができませんでした（起動中かもしれません）"
fi

echo ""

# ステップ4: ホストマシンのポートを確認
echo "🔌 ステップ4: ホストマシンのポート 80 を確認中..."
echo ""

if lsof -i :80 > /dev/null 2>&1 || netstat -tulpn 2>/dev/null | grep -q :80; then
    echo "✅ ポート 80 がバインドされています"
else
    echo "⚠️  ポート 80 が確認できません"
fi

echo ""

# ステップ5: ファイアウォール確認（Linux）
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "🔒 ステップ5: ファイアウォール設定を確認中..."
    echo ""
    
    if sudo ufw status > /dev/null 2>&1; then
        if sudo ufw status | grep -q "Status: active"; then
            echo "⚠️  UFW ファイアウォールが有効です。"
            echo "   以下を実行してください:"
            echo "   sudo ufw allow 80/tcp"
            echo "   sudo ufw allow 443/tcp"
        else
            echo "✅ UFW ファイアウォールは無効です"
        fi
    else
        echo "ℹ️  UFW ファイアウォール情報は取得できません"
    fi
    echo ""
fi

# ステップ6: アクセステスト方法を表示
echo "========================================="
echo "📝 他のPCからのアクセステスト方法"
echo "========================================="
echo ""
echo "以下のURLを他のPCのブラウザで開いてください："
echo ""
echo "  🌐 http://$HOST_IP"
echo ""
echo "テスト項目："
echo "  ✅ ログインページが表示されるか"
echo "  ✅ ユーザー登録できるか"
echo "  ✅ 試験を実施できるか（Part1）"
echo "  ✅ 成績が表示されるか"
echo "  ✅ 管理画面にアクセスできるか"
echo ""

# ステップ7: トラブルシューティング
echo "========================================="
echo "🔧 接続できない場合"
echo "========================================="
echo ""
echo "1️⃣  コンテナが起動しているか確認:"
echo "   docker-compose ps"
echo ""
echo "2️⃣  ログを確認:"
echo "   docker-compose logs -f app"
echo ""
echo "3️⃣  ネットワーク接続テスト:"
echo "   ping $HOST_IP"
echo "   telnet $HOST_IP 80"
echo ""
echo "4️⃣  ファイアウォール確認（Linux）:"
echo "   sudo ufw status"
echo "   sudo ufw allow 80/tcp"
echo ""
echo "5️⃣  ポート確認（Windows）:"
echo "   netstat -ano | findstr :80"
echo ""

# ステップ8: 監視モード
read -p "📊 リアルタイム監視モードを開始しますか？ (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "リアルタイム監視を開始します（Ctrl+C で終了）"
    echo ""
    
    while true; do
        clear
        echo "🌐 ネットワークアクセステスト - リアルタイム監視"
        echo "ホストIP: $HOST_IP"
        echo "========================================="
        echo ""
        echo "$(date)"
        echo ""
        
        # コンテナ状態
        echo "🐳 コンテナ状態:"
        docker-compose ps | tail -n +2 || echo "なし"
        echo ""
        
        # ネットワークテスト
        echo "🌐 ネットワークテスト:"
        if timeout 3 bash -c "echo > /dev/tcp/$HOST_IP/80" 2>/dev/null; then
            echo "✅ ポート 80 に接続可能"
        else
            echo "❌ ポート 80 に接続できません"
        fi
        echo ""
        
        # Webサーバーテスト
        echo "🌍 Webサーバーレスポンス:"
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 3 --max-time 5 http://$HOST_IP/ 2>/dev/null || echo "000")
        if [ "$HTTP_CODE" = "200" ]; then
            echo "✅ HTTP 200 OK"
        elif [ "$HTTP_CODE" = "302" ]; then
            echo "✅ HTTP 302 リダイレクト（ログインページへ）"
        elif [ "$HTTP_CODE" = "000" ]; then
            echo "⚠️  タイムアウト (確認中...)"
        else
            echo "⚠️  HTTP $HTTP_CODE"
        fi
        echo ""
        
        # Docker stats
        echo "📊 Docker リソース使用量:"
        docker stats --no-stream | tail -n +2 || echo "情報なし"
        echo ""
        
        echo "（30秒ごとに更新、Ctrl+C で終了）"
        sleep 30
    done
fi

echo ""
echo "✅ テスト完了"
