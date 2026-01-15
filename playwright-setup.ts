import { spawn } from "child_process";

export default async function globalTeardown() {
    // テスト完了後、ポート 8888 でレポートサーバーを起動
    console.log("\n📊 テスト完了。ポート 8888 でレポートを起動します...");

    spawn("npx", ["playwright", "show-report", "--host", "0.0.0.0", "--port", "8888"], {
        stdio: "inherit",
        detached: true,
    }).unref();

    // 短時間待機
    await new Promise(resolve => setTimeout(resolve, 1000));
}
