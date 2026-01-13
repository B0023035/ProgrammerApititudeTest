import { defineConfig, devices } from "@playwright/test";
import * as dotenv from "dotenv";
import { fileURLToPath } from "url";
import { dirname, resolve } from "path";

// ES Moduleで__dirnameを取得
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// .env.testingを読み込む
dotenv.config({ path: resolve(__dirname, ".env.testing") });

export default defineConfig({
    testDir: "./e2e",
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    timeout: 60 * 1000,

    reporter: [["html", { host: "0.0.0.0", port: 8888 }], ["list"]],

    use: {
        baseURL: process.env.TEST_BASE_URL || "http://localhost:80",
        trace: "on-first-retry",
        screenshot: "only-on-failure",
        video: "retain-on-failure",
    },

    projects: [
        {
            name: "chromium",
            use: {
                ...devices["Desktop Chrome"],
            },
        },
    ],

    // Docker で既に起動しているので webServer は不要
});
