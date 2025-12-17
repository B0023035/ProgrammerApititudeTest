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

        // 認証完了まで待機
        await this.page.waitForTimeout(1000);
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

        // 練習ページに遷移するのを待つ
        await this.page.waitForURL(url => url.pathname.includes("/guest/practice/1"), {
            timeout: 10000,
        });

        await this.page.waitForTimeout(1000);
    }

    async logout() {
        await this.page.click('[data-testid="user-menu"]');
        await this.page.click("text=ログアウト");
        await this.page.waitForURL("**/login", { timeout: 10000 });
    }
}
