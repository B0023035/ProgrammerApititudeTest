#!/bin/bash

# Playwright テスト実行
echo "🧪 Playwright テスト実行中..."
npx playwright test

# テスト完了後、レポートをコピー
echo "📋 レポートをコピー中..."
cp -r playwright-report/ public/playwright-report/

echo "✅ テスト完了！"
echo "📊 レポートは以下で確認できます："
echo "   http://localhost/playwright-report/"
echo "   http://localhost:9323/  (ポート 9323 でも確認可能)"
