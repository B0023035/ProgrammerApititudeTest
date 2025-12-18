import { test, expect } from "./fixtures/auth.fixture";
import { AuthHelper } from "./helpers/auth.helper";
import { testAccounts } from "./config/test-accounts";

// ====================================
// テスト設定
// ====================================
test.describe.configure({ mode: "parallel" });

// ====================================
// 1. セッションコード入力のテスト
// ====================================
test.describe("セッションコード認証", () => {
    test("正しいセッションコードで認証できる", async ({ page }) => {
        const auth = new AuthHelper(page);
        await page.goto("/");

        // セッションコード入力フォームが表示される
        await expect(page.locator("text=セッションコードを入力してください")).toBeVisible();

        await page.fill("input#session_code", testAccounts.sessionCode);
        await page.click('button:has-text("確認")');

        // Welcome画面に遷移
        await expect(page).toHaveURL(/.*welcome/);
    });

    test("誤ったセッションコードでエラーが表示される", async ({ page }) => {
        await page.goto("/");
        await page.fill("input#session_code", "INVALID-CODE");
        await page.click('button:has-text("確認")');

        // エラーメッセージが表示される
        await expect(page.locator(".text-red-600")).toBeVisible();
    });

    test("戻るボタンでセッションがクリアされる", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();

        // 戻るボタンをクリック
        await page.click('button:has-text("戻る")');

        // トップページに戻る
        await expect(page).toHaveURL("/");
    });
});

// ====================================
// 2. 認証関連のテスト
// ====================================
test.describe("認証機能", () => {
    test("ユーザーログインが正常に動作する", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await auth.loginAsUser();

        await expect(page).toHaveURL(/.*test-start/);
        await expect(page.locator("text=プログラマー適性検査")).toBeVisible();
    });

    test("管理者ログインが正常に動作する", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.loginAsAdmin();

        await expect(page).toHaveURL(/.*admin\/dashboard/);
        await expect(page.locator("text=管理者ダッシュボード")).toBeVisible();
    });

    test("誤った認証情報でログインできない", async ({ page }) => {
        await page.goto("/login");
        await page.fill('input[type="email"]', "wrong@example.com");
        await page.fill('input[type="password"]', "wrongpassword");

        // ログインボタンをクリック
        await page.click('button:has-text("Log in")');

        // エラーメッセージが表示される
        await expect(page.locator(".text-red-600")).toBeVisible();
    });

    test("ログアウトが正常に動作する", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await auth.loginAsUser();

        // プロフィールメニューからログアウト
        await auth.logout();

        await expect(page).toHaveURL(/.*login/);
    });
});

// ====================================
// 3. ゲストフロー
// ====================================
test.describe("ゲストユーザーフロー", () => {
    test("Welcomeページからゲストリンクをクリックできる", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();

        await page.click('a[href="/guest/info"]');
        await expect(page).toHaveURL(/.*guest\/info/);
    });

    test("ゲスト情報入力から練習問題まで進める", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await auth.setupGuest();

        // 練習ページに遷移して「第1部の練習を始める」ボタンが表示される
        await expect(page.locator("text=第1部の練習を始める")).toBeVisible();
    });

    test("所属と氏名が未入力の場合、始めるボタンが無効", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await page.goto("/guest/info");

        const button = page.locator('button:has-text("始める")');
        await expect(button).toBeDisabled();
    });

    test("氏名のみ入力の場合、始めるボタンが無効", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await page.goto("/guest/info");
        await page.fill("input#guest_name", testAccounts.guest.name);

        const button = page.locator('button:has-text("始める")');
        await expect(button).toBeDisabled();
    });

    test("ゲストで練習問題を開始できる", async ({ guestPage }) => {
        // フィクスチャを使用して、すでにゲスト情報入力済み
        await guestPage.click("text=第1部の練習を始める");
        await expect(guestPage).toHaveURL(/.*practice\/1/);

        // 練習開始ポップアップが表示される
        await expect(guestPage.locator("text=練習問題 第1部")).toBeVisible();
        await guestPage.click('button:has-text("練習を開始する")');

        // 問題が表示される
        await expect(guestPage.locator("text=/問 \\d+/")).toBeVisible();
    });
});

// ====================================
// 4. 練習問題のテスト
// ====================================
test.describe("練習問題機能", () => {
    test("問題に回答できる", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // 選択肢をクリック（大文字A ではなく小文字a に対応）
        const answerButton = authenticatedPage.locator('button:has-text("a")').first();
        await answerButton.click();

        // 選択されたことを確認(青色背景)
        await expect(answerButton).toHaveClass(/bg-blue-100/);
    });

    test("次の問題に進める", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        await authenticatedPage.locator('button:has-text("a")').first().click();
        await authenticatedPage.click('button:has-text("次の問題")');

        await expect(authenticatedPage.locator("text=問 2")).toBeVisible();
    });

    test("前の問題に戻れる", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        await authenticatedPage.click('button:has-text("次の問題")');
        await authenticatedPage.click('button:has-text("前の問題")');

        await expect(authenticatedPage.locator("text=問 1")).toBeVisible();
    });

    test("回答状況テーブルで問題にジャンプできる", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // 回答状況テーブルで問題3をクリック
        await authenticatedPage.click('td:has-text("3")');

        await expect(authenticatedPage.locator("text=問 3")).toBeVisible();
    });

    test("チェックボックスで問題にマークできる", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // チェックボックスをクリック（最初の1つだけ）
        const checkbox = authenticatedPage.locator('input[type="checkbox"]').first();
        await checkbox.click();

        // チェックされたことを確認
        await expect(checkbox).toBeChecked();
    });

    test("練習を完了できる（419エラーデバッグ付き）", async ({ authenticatedPage }) => {
        // 練習開始画面を表示
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // 数問に回答
        for (let i = 0; i < 2; i++) {
            const answerButton = authenticatedPage.locator('button:has-text("a")').first();
            await answerButton.click({ timeout: 10000 });
            
            // 次の問題へ進む（最後の問題でない場合）
            const nextButton = authenticatedPage.locator('button:has-text("次の問題")');
            const isDisabled = await nextButton.evaluate((el: any) => el.disabled);
            if (!isDisabled && i < 1) {
                await nextButton.click({ timeout: 10000 });
            }
        }

        // 練習完了ボタン
        await authenticatedPage.click('button:has-text("練習完了")', { timeout: 10000 });

        // 確認ダイアログで「確定」をクリック
        const confirmButton = authenticatedPage.locator('button:has-text("確定")');
        if (await confirmButton.isVisible({ timeout: 5000 })) {
            await confirmButton.click();
        }
    });

    test("タイマーが動作している", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        const timerText = await authenticatedPage
            .locator("text=/残り時間: \\d+:\\d+/")
            .textContent();
        expect(timerText).toMatch(/残り時間: \d+:\d+/);

        // 数秒待ってタイマーが減ることを確認
        await authenticatedPage.waitForTimeout(2000);
        const newTimerText = await authenticatedPage
            .locator("text=/残り時間: \\d+:\\d+/")
            .textContent();
        expect(newTimerText).not.toBe(timerText);
    });
});

// ====================================
// 5. 解説ページのテスト
// ====================================
test.describe("解説ページ機能", () => {
    test.setTimeout(25000);
    
    test("解説が正しく表示される", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // 回答して完了
        await authenticatedPage.click('button:has-text("a")');
        await authenticatedPage.click('button:has-text("練習完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 解説ページの要素を確認
        await expect(authenticatedPage.locator("text=解説 第1部")).toBeVisible();
        await expect(authenticatedPage.locator("text=判定:")).toBeVisible();
        await expect(authenticatedPage.locator("text=解説:")).toBeVisible();
    });

    test("本番試験へ進むボタンが機能する", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        await authenticatedPage.click('button:has-text("練習完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 本番へボタンをクリック
        await authenticatedPage.click('button:has-text("本番へ")');

        // 本番試験ページに遷移
        await expect(authenticatedPage).toHaveURL(/.*exam\/1/);
    });
});

// ====================================
// 6. 本番試験のテスト
// ====================================
test.describe("本番試験機能", () => {
    test.setTimeout(25000);
    
    test("本番試験を完走できる", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // 練習問題を完了
        await authenticatedPage.click('button:has-text("a")');
        await authenticatedPage.click('button:has-text("練習完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 本番へボタンをクリック
        await authenticatedPage.click('button:has-text("本番へ")');
        await authenticatedPage.waitForURL(/.*exam/, { timeout: 10000 });

        // 第1部を完了
        await authenticatedPage.click('button:has-text("試験を開始する")');
        await authenticatedPage.click('button:has-text("a")');
        await authenticatedPage.click('button:has-text("第1部完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 第2部開始
        await authenticatedPage.click('button:has-text("試験を開始する")');
        await authenticatedPage.click('button:has-text("a")');
        await authenticatedPage.click('button:has-text("第2部完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 第3部開始
        await authenticatedPage.click('button:has-text("試験を開始する")');
        await authenticatedPage.click('button:has-text("a")');
        await authenticatedPage.click('button:has-text("試験完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 結果ページに遷移
        await expect(authenticatedPage).toHaveURL(/.*result/);
        await expect(authenticatedPage.locator("text=修了証書")).toBeVisible();
    });

    test("本番試験の回答が保存される", async ({ authenticatedPage }) => {
        await authenticatedPage.click("text=始める");
        await authenticatedPage.click("text=第1部の練習を始める");
        await authenticatedPage.click('button:has-text("練習を開始する")');

        // 練習問題を完了
        await authenticatedPage.click('button:has-text("a")');
        await authenticatedPage.click('button:has-text("練習完了")');
        await authenticatedPage.click('button:has-text("OK")');

        // 本番へボタンをクリック
        await authenticatedPage.click('button:has-text("本番へ")');
        await authenticatedPage.waitForURL(/.*exam/, { timeout: 10000 });

        // 試験開始
        await authenticatedPage.click('button:has-text("試験を開始する")');

        // 回答を選択
        await authenticatedPage.click('button:has-text("a")');

        // ページをリロード
        await authenticatedPage.reload();

        // 回答が保持されていることを確認
        await expect(authenticatedPage.locator('button:has-text("a")')).toHaveClass(/bg-blue-100/);
    });
});

// ====================================
// 7. 結果ページのテスト
// ====================================
test.describe("結果ページ機能", () => {
    test.setTimeout(25000);
    
    test("修了証書が表示される", async ({ authenticatedPage }) => {
        await authenticatedPage.goto("/result");

        await expect(authenticatedPage.locator("text=修了証書")).toBeVisible();
        await expect(authenticatedPage.locator("svg")).toBeVisible(); // SVG証明書
    });

    test("PDFダウンロードボタンが機能する", async ({ authenticatedPage }) => {
        await authenticatedPage.goto("/result");

        // ダウンロードボタンが表示されている
        await expect(authenticatedPage.locator('button:has-text("ダウンロード")')).toBeVisible();
    });

    test("ランクが正しく表示される", async ({ authenticatedPage }) => {
        await authenticatedPage.goto("/result");

        // ランク表示を確認(Platinum, Gold, Silver, Bronzeのいずれか)
        const rankText = await authenticatedPage
            .locator("text=/Platinum|Gold|Silver|Bronze/")
            .textContent();
        expect(rankText).toMatch(/Platinum|Gold|Silver|Bronze/);
    });
});

// ====================================
// 8. 管理者機能のテスト
// ====================================
test.describe("管理者機能", () => {
    test("ダッシュボードに統計情報が表示される", async ({ adminPage }) => {
        await expect(adminPage.locator("text=管理者ダッシュボード")).toBeVisible();
        await expect(adminPage.locator("text=イベント管理")).toBeVisible();
        await expect(adminPage.locator("text=成績管理")).toBeVisible();
    });

    test("イベント一覧が表示される", async ({ adminPage }) => {
        await adminPage.click('a:has-text("イベント管理")');

        await expect(adminPage).toHaveURL(/.*admin\/events/);
        await expect(adminPage.locator("text=イベント管理")).toBeVisible();
    });

    test("新規イベントを作成できる", async ({ adminPage }) => {
        await adminPage.click('a:has-text("イベント管理")');
        await adminPage.click('a:has-text("新規イベント作成")');

        // フォーム入力
        await adminPage.fill("input#name", "テストイベント");

        // ランダム生成ボタンをクリック
        await adminPage.click('button:has-text("ランダム生成")');

        // パスフレーズが自動入力されたことを確認
        const passphrase = await adminPage.inputValue("input#passphrase");
        expect(passphrase.length).toBeGreaterThan(0);

        // 日時を設定
        const now = new Date();
        const begin = now.toISOString().slice(0, 16);
        const end = new Date(now.getTime() + 86400000).toISOString().slice(0, 16);

        await adminPage.fill("input#begin", begin);
        await adminPage.fill("input#end", end);

        // フル版を選択
        await adminPage.click('input[value="full"]');

        // 作成ボタンをクリック
        await adminPage.click('button:has-text("作成する")');

        // 成功後、一覧に戻る
        await expect(adminPage).toHaveURL(/.*admin\/events$/);
    });

    test("成績管理ページが表示される", async ({ adminPage }) => {
        await adminPage.click('a:has-text("成績管理")');

        await expect(adminPage).toHaveURL(/.*admin\/results/);
        await expect(adminPage.locator("table")).toBeVisible();
    });

    test("ログアウトできる", async ({ adminPage }) => {
        await adminPage.click('button:has-text("ログアウト")');

        // ログインページに戻る
        await expect(adminPage).toHaveURL(/.*admin\/login/);
    });
});

// ====================================
// 9. レスポンシブデザインのテスト
// ====================================
test.describe("レスポンシブデザイン", () => {
    test("モバイルビューで正しく表示される", async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 667 });

        await page.goto("/");
        await expect(page.locator('img[alt="YIC Logo"]')).toBeVisible();
    });

    test("タブレットビューで正しく表示される", async ({ page }) => {
        await page.setViewportSize({ width: 768, height: 1024 });

        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await auth.loginAsUser();
        await page.click("text=始める");

        await expect(page.locator("text=プログラマー適性検査")).toBeVisible();
    });
});

// ====================================
// 10. エラーハンドリングのテスト
// ====================================
test.describe("エラーハンドリング", () => {
    test("存在しないページで404が表示される", async ({ page }) => {
        const response = await page.goto("/nonexistent-page");
        expect(response?.status()).toBe(404);
    });

    test("セッション切れ時に適切にリダイレクトされる", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await auth.loginAsUser();

        // セッションをクリア(Cookie削除)
        await page.context().clearCookies();

        // 保護されたページにアクセス
        await page.goto("/exam/1");

        // ログインページにリダイレクト
        await expect(page).toHaveURL(/.*login/);
    });
});

// ====================================
// 11. パフォーマンステスト
// ====================================
test.describe("パフォーマンス", () => {
    test("ページ読み込みが3秒以内", async ({ page }) => {
        const startTime = Date.now();
        await page.goto("/");
        const loadTime = Date.now() - startTime;

        expect(loadTime).toBeLessThan(3000);
    });
});

// ====================================
// 12. アクセシビリティテスト
// ====================================
test.describe("アクセシビリティ", () => {
    test("キーボード操作で画面遷移できる", async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.enterSessionCode();
        await page.goto("/login");

        // Tabキーでフォーカス移動
        await page.keyboard.press("Tab");
        await page.keyboard.type(testAccounts.user.email);
        await page.keyboard.press("Tab");
        await page.keyboard.type(testAccounts.user.password);
        await page.keyboard.press("Enter");

        await expect(page).toHaveURL(/.*test-start/);
    });

    test("画像にalt属性が設定されている", async ({ page }) => {
        await page.goto("/");

        const images = await page.locator("img").all();
        for (const img of images) {
            const alt = await img.getAttribute("alt");
            expect(alt).toBeTruthy();
        }
    });
});
