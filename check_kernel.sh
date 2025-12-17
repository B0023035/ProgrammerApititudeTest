#!/bin/bash

echo "========================================="
echo "🔍 Kernel.php のミドルウェア設定確認"
echo "========================================="
echo ""

# app/Http/Kernel.php の web ミドルウェアグループを表示
echo "app/Http/Kernel.php の内容:"
docker-compose exec laravel.test cat app/Http/Kernel.php

echo ""
echo "========================================="
echo "重要: 以下のミドルウェアが含まれているか確認"
echo "========================================="
echo ""
echo "✅ 必須ミドルウェア:"
echo "  - StartSession (セッション開始)"
echo "  - VerifyCsrfToken (CSRF検証)"
echo "  - ShareErrorsFromSession (エラー共有)"
echo ""