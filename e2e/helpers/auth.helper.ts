import { Page } from "@playwright/test";
import { testAccounts, testUrls } from "../config/test-accounts";

export class AuthHelper {
    constructor(private page: Page) {}

    async enterSessionCode(code?: string) {
        await this.page.goto(testUrls.sessionEntry);
        await this.page.fill("input#session_code", code || testAccounts.sessionCode);
        await this.page.click('button:has-text("確認")');
        await this.page.waitForURL("**/welcome");
    }

    async loginAsUser() {
        await this.page.goto(testUrls.login);
        await this.page.fill('input[type="email"]', testAccounts.user.email);
        await this.page.fill('input[type="password"]', testAccounts.user.password);

        // ログインボタンをクリック - テキストで検索
        await this.page.click('button:has-text("Log in")');
        await this.page.waitForURL("**/test-start");
    }

    async loginAsAdmin() {
        await this.page.goto(testUrls.adminLogin);
        await this.page.fill("input#email", testAccounts.admin.email);
        await this.page.fill("input#password", testAccounts.admin.password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForURL("**/admin/dashboard");
    }

    async setupGuest(school?: string, name?: string) {
        await this.page.goto(testUrls.guestInfo);

        // 入力
        await this.page.fill("input#school_name", school || testAccounts.guest.school);
        await this.page.fill("input#guest_name", name || testAccounts.guest.name);

        // 始めるボタンをクリック（練習ページに遷移）
        await this.page.click('button:has-text("始める")');

        // 練習ページに遷移するのを待つ
        await this.page.waitForURL(url => !url.pathname.includes("/guest/practice/1"), {
            timeout: 10000,
        });

        // 「第1部の練習を始める」ボタンが表示されるまで待つ
        await this.page.waitForSelector("text=第1部の練習を始める", { timeout: 5000 });
    }

    async logout() {
        await this.page.click('[data-testid="user-menu"]');
        await this.page.click("text=ログアウト");
        await this.page.waitForURL("**/login");
    }
}
