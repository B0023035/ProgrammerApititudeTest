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
        }

        // 定期更新を開始（5分ごと）
        this.startPeriodicRefresh();

        // ページ可視化時に更新
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                this.refreshTokenAsync();
            }
        });

        // ウィンドウフォーカス時に更新（60秒以上経過している場合）
        window.addEventListener("focus", () => {
            const elapsed = Date.now() - this.lastRefreshTime;
            if (elapsed > 60000) {
                this.refreshTokenAsync();
            }
        });
    }

    private startPeriodicRefresh(): void {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        // 5分ごとに定期更新（軽量化）
        this.refreshInterval = window.setInterval(() => {
            this.refreshTokenAsync();
        }, 300000);
    }

    public async ensureFreshToken(): Promise<string> {
        // 既に更新中の場合は、その更新を待つ
        if (this.refreshPromise) {
            return this.refreshPromise;
        }

        // 最後の更新から30秒以内の場合はスキップ（軽量化）
        const elapsed = Date.now() - this.lastRefreshTime;
        if (elapsed < 30000 && this.lastRefreshTime > 0 && this.currentToken) {
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
            this.lastRefreshTime = Date.now();

            // カスタムイベントを発行
            window.dispatchEvent(
                new CustomEvent("csrf-token-refreshed", {
                    detail: { token: newToken, timestamp: this.lastRefreshTime },
                })
            );

            return newToken;
        } catch (error) {
            this.lastRefreshTime = 0; // 次回すぐ再試行
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

    }

    private refreshTokenAsync(): void {
        this.ensureFreshToken().catch(() => {});
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

// ★★★ axios インターセプタで POST 系リクエストのトークン更新を確実に行う ★★★
axios.interceptors.request.use(async config => {
    // ★ credentialsを確保（セッションCookieをリクエストに含める）
    config.withCredentials = true;
    
    // POST/PUT/PATCH/DELETE の場合のみトークンを更新
    if (config.method && ["post", "put", "patch", "delete"].includes(config.method.toLowerCase())) {
        try {
            // トークン更新を待つ
            const freshToken = await tokenManager.ensureFreshToken();
            // ヘッダーに設定
            config.headers["X-CSRF-TOKEN"] = freshToken;
        } catch {
            // エラー時は既存トークンを使用
        }
    }
    return config;
});

// ★★★ axios 応答インターセプター：419エラー時に自動リロード ★★★
axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response && error.response.status === 419) {
            console.warn("419 Page Expired detected - reloading page to get fresh CSRF token");
            // 少し待ってからリロード（ユーザーが状況を把握できるよう）
            setTimeout(() => {
                window.location.reload();
            }, 100);
        }
        return Promise.reject(error);
    }
);

// ★★★ Inertia Router グローバルフック ★★★

// ★ Inertia リクエスト前：credentials を「include」に設定
router.on("before", event => {
    // ★ すべてのリクエストに credentials を含める
    if (!event.detail.visit.options) {
        event.detail.visit.options = {};
    }
    (event.detail.visit.options as any).credentials = "include";
});

// ★ 無効なレスポンス（419エラーなど）を検出
router.on("invalid", event => {
    const response = event.detail.response;
    if (response && response.status === 419) {
        console.warn("Inertia: 419 Page Expired detected - reloading page");
        event.preventDefault();
        window.location.reload();
    }
});

// ページ遷移成功時
router.on("success", () => {});

// エラー発生時
router.on("error", event => {
    const errors = event.detail.errors;
    // 419エラー（CSRF token mismatch）の検出
    if (errors) {
        const errorString = JSON.stringify(errors);
        if (
            errorString.includes("419") ||
            errorString.includes("expired") ||
            errorString.includes("CSRF")
        ) {
            alert("セッションの有効期限が切れました。ページを再読み込みします。");
            window.location.reload();
        }
    }
});

// リクエスト完了時（成功・失敗問わず）
router.on("finish", () => {});

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
