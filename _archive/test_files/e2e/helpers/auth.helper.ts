import { Page } from "@playwright/test";
import { testAccounts, testUrls } from "../config/test-accounts";

export class AuthHelper {
    constructor(private page: Page) {}

    async enterSessionCode(code?: string) {
        // ★ まずCSRF Cookieを取得
        await this.page.goto("/sanctum/csrf-cookie");
        await this.page.waitForTimeout(500);

        await this.page.goto(testUrls.sessionEntry);
        await this.page.fill("input#session_code", code || testAccounts.sessionCode);
        await this.page.click('button:has-text("確認")');
        await this.page.waitForURL("**/welcome", { timeout: 10000 });

        // セッションが保存されるまで待機
        await this.page.waitForTimeout(1000);
    }

    async loginAsUser() {
        await this.page.goto(testUrls.login);
        await this.page.fill('input[type="email"]', testAccounts.user.email);
        await this.page.fill('input[type="password"]', testAccounts.user.password);
        await this.page.click('button:has-text("Log in")');
        await this.page.waitForURL("**/test-start", { timeout: 10000 });

        // ★ ログイン後にCSRF Cookieを再取得（セッションが変わるため）
        await this.page.goto("/sanctum/csrf-cookie");
        await this.page.waitForTimeout(300);
        await this.page.goto("/test-start");
        await this.page.waitForTimeout(500);
    }

    async loginAsAdmin() {
        // ★ CSRF Cookieを取得
        await this.page.goto("/sanctum/csrf-cookie");
        await this.page.waitForTimeout(500);

        await this.page.goto(testUrls.adminLogin);
        await this.page.fill("input#email", testAccounts.admin.email);
        await this.page.fill("input#password", testAccounts.admin.password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForURL("**/admin/dashboard", { timeout: 10000 });

        await this.page.waitForTimeout(1000);
    }

    async setupGuest(school?: string, name?: string) {
        await this.page.goto(testUrls.guestInfo);
        await this.page.fill("input#school_name", school || testAccounts.guest.school);
        await this.page.fill("input#guest_name", name || testAccounts.guest.name);
        await this.page.click('button:has-text("始める")');

        // ExamInstructions ページが表示されるまで待機
        await this.page.waitForSelector("text=第1部の練習を始める", { timeout: 10000 });

        await this.page.waitForTimeout(1000);
    }

    async logout() {
        // ユーザーメニュー（名前が表示されたドロップダウンボタン）をクリック
        // ResponsiveUserProfile.vue のドロップダウントリガー - SVG付きのボタン
        const userDropdown = this.page.locator('button:has(svg[stroke="currentColor"])').first();
        await userDropdown.click();
        await this.page.waitForTimeout(500);

        // ドロップダウンが開くのを待つ - DropdownLink内のボタン（px-4クラスを持つもの）
        // レスポンシブナビゲーション（sm:hidden）内のボタンではなく、Dropdown内のボタンを選択
        const logoutButton = this.page.locator('button.px-4:has-text("ログアウト")').first();
        await logoutButton.waitFor({ state: "visible", timeout: 5000 });

        // ログアウトリンクをクリック
        await logoutButton.click();
        // ユーザーのログアウト後はウェルカムページ(/welcome)にリダイレクトされる
        await this.page.waitForURL("**/welcome", { timeout: 10000 });
    }
}
