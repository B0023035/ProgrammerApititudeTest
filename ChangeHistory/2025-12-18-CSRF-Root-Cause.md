# CSRF 419 エラー 根本原因と修正計画 - 2025-12-18

## 問題の根本原因

### 1. **GET メソッド許可による CSRF トークン無効化**

```php
// routes/web.php (現在)
Route::match(['post', 'get'], '/complete', [PracticeController::class, 'complete'])
```

- POST リクエスト時に Inertia.js が CSRF トークンを送信
- ただし、同じルートが GET も許可しているため、潜在的に不整合が発生
- **修正**: POST のみに限定

### 2. **Inertia.js での CSRF トークン処理**

- `form.post()` は POST リクエスト時に CSRF トークンを自動送信
- しかし、セッション内の `_token` とリクエストの CSRF トークンが一致しない場合、419 エラー発生
- **原因**: セッションが複数の worker で共有される際、ファイルドライバの破壊

### 3. **セッション設定の不整合**

- `.env`: `SESSION_DRIVER=redis` ✓ 正しい
- `.env.testing`: `SESSION_DRIVER=database` ✓ 正しい
- ただし、以前 `SESSION_DRIVER=file` だった時の影響が残っている可能性

## 修正内容

### 修正1: ルートを POST のみに限定

**ファイル**: routes/web.php (2箇所)

```php
// 変更前
Route::match(['post', 'get'], '/complete', ...)

// 変更後
Route::post('/complete', ...)
```

### 修正2: Inertia Request ミドルウェアの確認

**ファイル**: app/Http/Middleware/HandleInertiaRequests.php

- CSRF トークンが正しく Props に含まれているか確認

### 修正3: VerifyCsrfToken ミドルウェアの確認

**ファイル**: app/Http/Middleware/VerifyCsrfToken.php

- 除外ルートに不要なものがないか確認

### 修正4: セッションキャッシュのクリア

```bash
redis-cli FLUSHDB
# または
Cache::flush()
```

## 実装順序

1. routes/web.php を POST のみに修正
2. キャッシュをクリア
3. Laravel サーバーを再起動
4. ブラウザでテスト

## 期待される効果

- CSRF トークンが一貫性を保つ
- セッション複製がなくなる
- 419 エラーが解消される
