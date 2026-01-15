#!/bin/bash

# Playwright テスト実行
echo "🚀 Playwright テスト実行中..."
npx playwright test --reporter=list

# テスト完了後、ポート 8888 でレポート表示
echo ""
echo "📊 テストレポートをポート 8888 で起動..."
echo "📍 ブラウザで http://localhost:8888 にアクセス"
npx playwright show-report playwright-report --host 0.0.0.0 --port 8888
