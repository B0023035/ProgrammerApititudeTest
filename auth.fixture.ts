import { test as base, Page } from "@playwright/test";
import { testAccounts, testUrls } from "../config/test-accounts";

type AuthFixtures = {
    authenticatedPage: Page;
    adminPage: Page;
    guestPage: Page;
};

export const test = base.extend<AuthFixtures>({
    authenticatedPage: async ({ page }, use) => {
        // ★ 重要: 古いCookieを完全にクリア
        await page.context().clearCookies();

        await page.goto(testUrls.sessionEntry);
        await page.fill("input#session_code", testAccounts.sessionCode);
        await page.click('button:has-text("確認")');
        await page.waitForURL("**/welcome");

        await page.goto(testUrls.login);
        await page.fill('input[type="email"]', testAccounts.user.email);
        await page.fill('input[type="password"]', testAccounts.user.password);
        await page.click('button:has-text("Log in")');
        await page.waitForURL("**/test-start");

        await use(page);
    },

    adminPage: async ({ page }, use) => {
        // ★ 重要: 古いCookieを完全にクリア
        await page.context().clearCookies();

        await page.goto(testUrls.adminLogin);
        await page.fill("input#email", testAccounts.admin.email);
        await page.fill("input#password", testAccounts.admin.password);
        await page.click('button[type="submit"]');
        await page.waitForURL("**/admin/dashboard");

        await use(page);
    },

    guestPage: async ({ page }, use) => {
        // ★ 重要: 古いCookieを完全にクリア
        await page.context().clearCookies();

        await page.goto(testUrls.sessionEntry);
        await page.fill("input#session_code", testAccounts.sessionCode);
        await page.click('button:has-text("確認")');
        await page.waitForURL("**/welcome");

        await page.goto(testUrls.guestInfo);
        await page.fill("input#school_name", testAccounts.guest.school);
        await page.fill("input#guest_name", testAccounts.guest.name);
        await page.click('button:has-text("始める")');
        await page.waitForURL("**/guest/practice/1");

        await use(page);
    },
});

export { expect } from "@playwright/test";
