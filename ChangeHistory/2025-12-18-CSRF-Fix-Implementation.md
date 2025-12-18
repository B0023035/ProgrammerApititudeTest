# CSRF 419 修正実装 - 2025-12-18

## 実施した修正

### 修正1: routes/web.php - ゲスト用練習問題完了ルート
**変更前**:
```php
Route::match(['post', 'get'], '/complete', [PracticeController::class, 'guestComplete'])->name('complete');
```

**変更後**:
```php
Route::post('/complete', [PracticeController::class, 'guestComplete'])->name('complete');
```

**理由**: GET メソッドを削除することで、同じエンドポイントへの異なるメソッドでの重複アクセスを防止

**影響範囲**: ゲストユーザーの練習問題完了処理

---

### 修正2: routes/web.php - 認証ユーザー用練習問題完了ルート
**変更前**:
```php
Route::match(['post', 'get'], '/complete', [PracticeController::class, 'complete'])->name('complete');
```

**変更後**:
```php
Route::post('/complete', [PracticeController::class, 'complete'])->name('complete');
```

**理由**: 同上

**影響範囲**: 認証済みユーザーの練習問題完了処理

---

### 修正3: キャッシュクリア
```bash
rm -rf bootstrap/cache/*
```

**理由**: ルート設定キャッシュを更新するため

---

### 修正4: Laravel サーバー再起動
```bash
pkill -f "php artisan serve"
php artisan serve --host=0.0.0.0 --port=8000
```

**理由**: ルート変更を反映させるため

---

## 修正の効果

### CSRF トークンエラーが解消される仕組み

1. **GET リクエストの排除**
   - POST のみに限定することで、CSRF 検証が一貫して適用される

2. **Inertia.js の CSRF 処理**
   - POST リクエストのみを受け付けることで、Inertia.js の自動 CSRF トークン処理が正常に機能

3. **セッション一貫性**
   - 同じエンドポイントでの異なるメソッドによるセッション重複がなくなる

---

## 検証方法

### 1. ブラウザでのテスト
```
1. http://localhost:8000/ にアクセス
2. セッションコードを入力
3. 練習問題を実施
4. 「練習完了ボタン」を押す
5. → 419 エラーが出ないことを確認
```

### 2. Playwright テスト実行
```bash
npx playwright test
```

期待結果: すべてのテストが合格

### 3. ネットワークインスペクタの確認
```
1. ブラウザの開発者ツールを開く
2. Network タブで練習完了ボタン押下時のリクエストを確認
3. POST リクエストが 200 OK で応答することを確認
4. Response Headers に Set-Cookie があることを確認
```

---

## 期待される改善

- ✅ Practice.vue の練習完了ボタン押下後の 419 エラーが解消
- ✅ セッションコード入力画面でのエラーも解消
- ✅ Playwright テスト実行時のエラーが減少

---

## 追加確認項目

- [ ] ExamController の complete-part ルートも確認（同じパターンの可能性）
- [ ] その他の POST ルートが GET も許可していないか確認
