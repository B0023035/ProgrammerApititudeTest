import { test as base, Page, expect, BrowserContext } from "@playwright/test";
import { testAccounts, testUrls } from "../config/test-accounts";

type AuthFixtures = {
    authenticatedPage: Page;
    adminPage: Page;
    guestPage: Page;
};

export const test = base.extend<AuthFixtures>({
    authenticatedPage: async ({ page, context }, use) => {
        // ★ 1. CSRF Cookie を取得
        await page.goto("/sanctum/csrf-cookie");
        await page.waitForTimeout(500);

        // ★ 2. セッションコード入力
        await page.goto(testUrls.sessionEntry);
        await page.fill("input#session_code", testAccounts.sessionCode);
        await page.click('button:has-text("確認")');
        await page.waitForURL("**/welcome", { timeout: 10000 });
        await page.waitForTimeout(500);

        // ★ 3. ログイン
        await page.goto(testUrls.login);
        await page.fill('input[type="email"]', testAccounts.user.email);
        await page.fill('input[type="password"]', testAccounts.user.password);
        await page.click('button:has-text("Log in")');
        await page.waitForURL("**/test-start", { timeout: 10000 });
        await page.waitForTimeout(500);

        // ★ 4. ログイン後にCSRF Cookieを再取得（セッションが変わるため）
        await page.goto("/sanctum/csrf-cookie");
        await page.waitForTimeout(300);
        await page.goto("/test-start");
        await page.waitForTimeout(500);

        await use(page);
    },

    adminPage: async ({ page }, use) => {
        // ★ CSRF Cookie を取得
        await page.goto("/sanctum/csrf-cookie");
        await page.waitForTimeout(500);

        await page.goto(testUrls.adminLogin);
        await page.fill("input#email", testAccounts.admin.email);
        await page.fill("input#password", testAccounts.admin.password);
        await page.click('button[type="submit"]');
        await page.waitForURL("**/admin/dashboard", { timeout: 10000 });
        await page.waitForTimeout(500);

        // ★ ログイン後にCSRF Cookieを再取得（セッションが変わるため）
        await page.goto("/sanctum/csrf-cookie");
        await page.waitForTimeout(300);
        
        // ダッシュボードに戻る
        await page.goto("/admin/dashboard");
        await page.waitForTimeout(500);

        await use(page);
    },

    guestPage: async ({ page }, use) => {
        // ★ CSRF Cookie を取得
        await page.goto("/sanctum/csrf-cookie");
        await page.waitForTimeout(500);

        // セッションコード入力
        await page.goto(testUrls.sessionEntry);
        await page.fill("input#session_code", testAccounts.sessionCode);
        await page.click('button:has-text("確認")');
        await page.waitForURL("**/welcome", { timeout: 10000 });
        await page.waitForTimeout(500);

        // ゲスト情報入力
        await page.goto(testUrls.guestInfo);
        await page.fill("input#school_name", testAccounts.guest.school);
        await page.fill("input#guest_name", testAccounts.guest.name);
        await page.click('button:has-text("始める")');

        // ExamInstructions ページが表示されるまで待機
        await page.waitForSelector('text=第1部の練習を始める', { timeout: 10000 });
        await page.waitForTimeout(500);

        await use(page);
    },
});

export { expect };
