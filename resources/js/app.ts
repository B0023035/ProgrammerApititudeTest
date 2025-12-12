import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, DefineComponent, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { router } from "@inertiajs/vue3";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// ★★★ CSRFトークン管理クラス - 419エラー完全解決 ★★★
class CSRFTokenManager {
    private static instance: CSRFTokenManager;
    private currentToken: string = "";
    private isRefreshing: boolean = false;
    private lastRefreshTime: number = 0;
    private refreshPromise: Promise<string> | null = null;
    private refreshInterval: number | null = null;

    private constructor() {
        this.initialize();
    }

    public static getInstance(): CSRFTokenManager {
        if (!CSRFTokenManager.instance) {
            CSRFTokenManager.instance = new CSRFTokenManager();
        }
        return CSRFTokenManager.instance;
    }

    private initialize(): void {
        // 初期トークンを取得
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            this.currentToken = meta.getAttribute("content") || "";
            console.log("✅ [CSRF] 初期トークン取得:", this.currentToken.substring(0, 20) + "...");
        }

        // 定期更新を開始（90秒ごと）
        this.startPeriodicRefresh();

        // ページ可視化時に更新
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                console.log("👁️ [CSRF] ページ表示 - トークン更新");
                this.refreshTokenAsync();
            }
        });

        // ウィンドウフォーカス時に更新（30秒以上経過している場合）
        window.addEventListener("focus", () => {
            const elapsed = Date.now() - this.lastRefreshTime;
            if (elapsed > 30000) {
                console.log("🎯 [CSRF] フォーカス復帰 - トークン更新");
                this.refreshTokenAsync();
            }
        });
    }

    private startPeriodicRefresh(): void {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // 90秒ごとに定期更新
        this.refreshInterval = window.setInterval(() => {
            console.log("⏰ [CSRF] 定期更新実行");
            this.refreshTokenAsync();
        }, 90000);
    }

    public async ensureFreshToken(): Promise<string> {
        // 既に更新中の場合は、その更新を待つ
        if (this.refreshPromise) {
            console.log("⏳ [CSRF] 更新待機中...");
            return this.refreshPromise;
        }

        // 最後の更新から5秒以内の場合はスキップ
        const elapsed = Date.now() - this.lastRefreshTime;
        if (elapsed < 5000 && this.lastRefreshTime > 0 && this.currentToken) {
            console.log(`✓ [CSRF] 更新スキップ (${Math.floor(elapsed / 1000)}秒前に更新済み)`);
            return Promise.resolve(this.currentToken);
        }

        // 新しい更新を開始
        this.refreshPromise = this.refreshToken();

        try {
            const token = await this.refreshPromise;
            return token;
        } finally {
            this.refreshPromise = null;
        }
    }

    private async refreshToken(): Promise<string> {
        if (this.isRefreshing) {
            return this.currentToken;
        }

        this.isRefreshing = true;
        const startTime = Date.now();
        console.log("🔄 [CSRF] トークン更新開始...", new Date().toLocaleTimeString());

        try {
            // ステップ1: CSRFクッキーを更新
            const cookieResponse = await fetch("/sanctum/csrf-cookie", {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!cookieResponse.ok) {
                throw new Error(`CSRF cookie failed: ${cookieResponse.status}`);
            }

            // ステップ2: 新しいトークンを取得
            const tokenResponse = await fetch("/csrf-token", {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!tokenResponse.ok) {
                throw new Error(`Token fetch failed: ${tokenResponse.status}`);
            }

            const data = await tokenResponse.json();
            const newToken = data.token;

            if (!newToken) {
                throw new Error("Token not found in response");
            }

            // ステップ3: トークンを更新
            this.updateToken(newToken);

            const elapsed = Date.now() - startTime;
            console.log(`✅ [CSRF] 更新成功 (${elapsed}ms)`, new Date().toLocaleTimeString());

            this.lastRefreshTime = Date.now();

            // カスタムイベントを発行
            window.dispatchEvent(
                new CustomEvent("csrf-token-refreshed", {
                    detail: { token: newToken, timestamp: this.lastRefreshTime },
                })
            );

            return newToken;
        } catch (error) {
            console.error("❌ [CSRF] 更新失敗:", error);
            this.lastRefreshTime = 0; // 次回すぐ再試行

            // 既存のトークンを返す
            return this.currentToken;
        } finally {
            this.isRefreshing = false;
        }
    }

    private updateToken(token: string): void {
        if (!token || token === this.currentToken) {
            return;
        }

        this.currentToken = token;

        // metaタグを更新
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute("content", token);
        }

        // すべてのフォームの_tokenを更新
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            (input as HTMLInputElement).value = token;
        });

        // axiosヘッダーを更新
        if ((window as any).axios) {
            (window as any).axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
        }

        console.log("📝 [CSRF] トークン適用完了:", token.substring(0, 20) + "...");
    }

    private refreshTokenAsync(): void {
        this.ensureFreshToken().catch(error => {
            console.error("[CSRF] 非同期更新エラー:", error);
        });
    }

    public getCurrentToken(): string {
        return this.currentToken;
    }

    public getLastRefreshTime(): number {
        return this.lastRefreshTime;
    }

    public forceRefresh(): Promise<string> {
        this.lastRefreshTime = 0;
        return this.ensureFreshToken();
    }
}

// グローバルインスタンスを作成
const tokenManager = CSRFTokenManager.getInstance();

// ★★★ Inertia.js アプリケーション設定 ★★★
createInertiaApp({
    title: title => `${title} - ${appName}`,
    resolve: name =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // ★ グローバルプロパティとして登録
        app.config.globalProperties.$refreshCSRF = () => tokenManager.ensureFreshToken();

        return app.mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});

// ★★★ Inertia Router グローバルフック ★★★

// すべてのリクエスト前にCSRFトークンを更新
router.on("before", async event => {
    const method = event.detail.visit.method.toLowerCase();
    const url = event.detail.visit.url;

    console.log(`🚀 [Inertia] ${method.toUpperCase()} ${url.pathname}`);

    // POST/PUT/PATCH/DELETE の場合は必ずトークンを更新
    if (["post", "put", "patch", "delete"].includes(method)) {
        console.log("📝 [Inertia] POST系リクエスト - CSRF更新");
        try {
            const token = await tokenManager.ensureFreshToken();
            console.log("✓ [Inertia] CSRF更新完了:", token.substring(0, 20) + "...");
        } catch (error) {
            console.error("❌ [Inertia] CSRF更新失敗:", error);
        }
    }
});

// ページ遷移成功時
router.on("success", event => {
    console.log("✅ [Inertia] ページ遷移成功");
});

// エラー発生時
router.on("error", event => {
    const errors = event.detail.errors;
    console.error("❌ [Inertia] エラー:", errors);

    // 419エラー（CSRF token mismatch）の検出
    if (errors) {
        const errorString = JSON.stringify(errors);
        if (
            errorString.includes("419") ||
            errorString.includes("expired") ||
            errorString.includes("CSRF")
        ) {
            console.error("🚨 [CSRF] 419エラー検出 - ページリロード");
            alert("セッションの有効期限が切れました。ページを再読み込みします。");
            window.location.reload();
        }
    }
});

// リクエスト完了時（成功・失敗問わず）
router.on("finish", event => {
    console.log("🏁 [Inertia] リクエスト完了");
});

// ★★★ グローバルに公開（Vueコンポーネント・デバッグ用）★★★
(window as any).refreshCSRFToken = () => tokenManager.ensureFreshToken();
(window as any).forceRefreshCSRF = () => tokenManager.forceRefresh();
(window as any).getCurrentCSRFToken = () => tokenManager.getCurrentToken();
(window as any).getCSRFLastRefresh = () => {
    const time = tokenManager.getLastRefreshTime();
    if (time === 0) return "未更新";
    const elapsed = Math.floor((Date.now() - time) / 1000);
    return `${elapsed}秒前`;
};
(window as any).csrfTokenManager = tokenManager;

// デバッグ情報を表示
console.log("✅ [CSRF] Token Manager initialized");
console.log("📌 [CSRF] デバッグコマンド:");
console.log("   - window.getCurrentCSRFToken()  : 現在のトークン取得");
console.log("   - window.getCSRFLastRefresh()   : 最終更新時刻");
console.log("   - window.refreshCSRFToken()     : トークン更新");
console.log("   - window.forceRefreshCSRF()     : 強制更新");
