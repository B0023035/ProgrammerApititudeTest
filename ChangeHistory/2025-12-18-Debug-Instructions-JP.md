# 問題の再現と検証手順
## CSRFトークンエラー & 回答状態の判定エラー

### 最初に確認すること

#### 1. サーバーが起動しているか確認
```bash
docker-compose ps
```

すべてのコンテナが "Up" 状態であることを確認してください。

#### 2. Redisセッションが動作しているか確認
```bash
docker-compose exec redis redis-cli ping
# Expected: PONG
```

---

## 問題1: CSRFトークンエラーの再現と検証

### ステップ1: ブラウザを開く
1. Chrome/Firefox/Edgeで `http://localhost` にアクセス
2. **F12** を押して「開発者ツール」を開く
3. **「Network」タブ** を開く
4. **フィルタを「XHR」に設定** - Fetch/Ajax通信のみを表示

### ステップ2: 練習問題ページへナビゲート
1. ゲストモード（「始める」ボタン）またはログイン
2. 「練習問題」ページを開く（第1部）

### ステップ3: 回答を入力
1. 問題に回答を1つ選択
2. ページの **「Network」タブで「complete」リクエスト** を探す

### ステップ4: CSRF トークンを確認
**リクエストの「Request」内容を確認**:
```
POST /practice/complete

Request Body:
_token: abc123...xyz (✓ 必須)
practiceSessionId: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
part: 1
answers: {"1":"A","2":"B",...}
timeSpent: 300
totalQuestions: 20
```

**確認点**:
- [ ] `_token` フィールドが存在する
- [ ] `_token` が長い文字列（トークン）である
- [ ] `_token` が空でない

### ステップ5: レスポンスを確認
**レスポンスのStatus Codeを確認**:
- **200** = OK (正常)
- **419** = Page Expired (CSRFエラー)
- **422** = Validation Failed (バリデーションエラー)
- **500** = Server Error

**419エラーが表示される場合**:
1. リクエストの`_token`フィールドをコピー
2. Laravelログで確認:
   ```bash
   tail -50 storage/logs/laravel.log | grep -i "token\|csrf\|419"
   ```

**422エラーが表示される場合**:
1. レスポンスボディにエラーメッセージが表示
2. どのフィールドが無効か確認
3. Request Bodyと比較

---

## 問題2: 回答状態の判定エラーの再現と検証

### ステップ1: ブラウザコンソールを開く
1. F12 → **「Console」タブ** を選択
2. コンソール画面を大きく表示（今後のログが見やすい）

### ステップ2: 練習ページに移動
1. 「練習問題」 → 「第1部」を開く
2. **「練習を開始する」をクリック**

### ステップ3: 問題に回答
1. **1問目の選択肢（例：「A」）をクリック**
2. コンソールに以下のメッセージが出力されることを確認:

```
=== handleAnswer ===
currentIndex: 0
selected answer: A
answerStatus length: 4
currentQuestion: {id: 1, part: 1, text: "...", ...}
Updated answerStatus[0]: {selected: "A", ...}

=== updateFormAnswers debug ===
answerStatus length: 4
questions length: 4
Question 0: ID=1, Answer=A
Final answers object: {1: "A"}
Total questions: 4
=== end debug ===
```

### ステップ4: エラーがないか確認
**以下のメッセージが出力されていないか確認**:
- `Question X: ID is undefined!` ← IDが見つからない
- `Invalid currentIndex!` ← インデックスエラー
- `Invalid answer label:` ← 不正な選択肢

### ステップ5: 複数問に回答
1. 2問目、3問目にも回答
2. コンソールで `Final answers object:` に複数のIDが含まれることを確認

```
Final answers object: {1: "A", 2: "B", 3: "C"}
```

### ステップ6: 練習完了してみる
1. 「練習完了」ボタンをクリック
2. コンソールで CSRF トークンのログを確認:

```
=== completePractice 呼び出し ===
_token: abc123...xyz
answers: {1: "A", 2: "B", ...}
```

3. **Network タブで complete リクエストを確認**:
   - Status: 200 (成功) または 419 (CSRF エラー)
   - Request Body に answers が含まれているか

---

## 問題が確認されたときのレポート

以下の情報をコレクトして報告してください:

### CSRFエラーの場合

```
【CSRFトークンエラーの再現レポート】

■ ブラウザ: Chrome/Firefox/Safari
■ OS: Windows/Mac/Linux

■ エラー内容:
- Network タブでの Status Code: 419/422/500
- エラーメッセージ: [正確に記述]

■ CSRF トークン情報:
- Request Body に _token が存在するか: ✓/✗
- _token の値: [最初の30文字]abc...
- _token の長さ: [例: 80文字]

■ Laravelログ出力:
[tail -50 storage/logs/laravel.log の出力]

■ ブラウザコンソール出力:
[コンソールのエラーメッセージ]
```

### 回答状態エラーの場合

```
【回答状態の判定エラーの再現レポート】

■ ブラウザコンソール出力:
[=== handleAnswer === から === end debug === までのログ]

■ 回答内容:
- 問1: [選択した選択肢]
- 問2: [選択した選択肢]
- 問3: [選択した選択肢]

■ Final answers object の内容:
{[コンソールから正確にコピー]}

■ 問題の症状:
- 回答したのに「未回答」と表示される
- 「出来ていない判定」が出る
- 他: [具体的に記述]

■ Laravelログ出力:
[tail -50 storage/logs/laravel.log の出力]
```

---

## よくあるQ&A

### Q: コンソールにログが出ない
**A**: 以下を確認してください:
1. ブラウザのキャッシュをクリア (Ctrl+Shift+Delete)
2. ページを再読み込み (Ctrl+Shift+R)
3. ブラウザを完全に再起動
4. 別のブラウザで試してみる

### Q: _token が空の場合
**A**: これは重大な問題です:
1. `meta name="csrf-token"` がHTMLに存在するか確認
2. HandleInertiaRequests が csrf_token を共有しているか確認
3. Practice.vue の form 初期化を確認

### Q: Question ID が undefined の場合
**A**: これは問題がサーバーから来ています:
1. `/practice/1` ページを再読み込み
2. ページソースから questions データを確認
3. サーバーのデータベースで question テーブルに id が存在するか確認

---

## 次のステップ

デバッグ情報をコレクトしたら:
1. ChangeHistory フォルダにレポートを保存
2. サーバーログを `storage/logs/laravel.log` から確認
3. 具体的なエラーメッセージをドキュメント化

