#!/bin/bash
# playwright-run-and-report.sh
# npx playwright test 実行後、自動でポート 8888 でレポートを起動

npx playwright test "$@"
TEST_EXIT_CODE=$?

# テスト実行後、ポート 8888 でレポートを起動
echo "📊 レポートをポート 8888 で起動します..."
npx playwright show-report --host 0.0.0.0 --port 8888 &

exit $TEST_EXIT_CODE
