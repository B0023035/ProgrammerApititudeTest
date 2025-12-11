import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, DefineComponent, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// グローバルレベルで CSRF トークンを管理
let globalCsrfToken = "";

const updateGlobalCsrfToken = (token: string) => {
    if (token && token !== globalCsrfToken) {
        globalCsrfToken = token;
        // メタタグも同期
        const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
        if (meta) {
            meta.setAttribute("content", token);
        }
        // axios ヘッダーも同期
        if (window.axios) {
            window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
        }
        console.log("[App CSRF] Token updated:", token.substring(0, 20) + "...");
    }
};

createInertiaApp({
    title: title => `${title} - ${appName}`,
    resolve: name =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        // 初期化時に CSRF トークンを設定
        const initialToken =
            props.initialPage.props?.csrf ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
            "";
        updateGlobalCsrfToken(initialToken);

        // ページ遷移前に CSRF トークンが送信されることを確認
        plugin.on("before", visit => {
            // form.post() の際、CSRF トークンが含まれていることを確認
            const token =
                globalCsrfToken ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
                "";
            if (token) {
                if (window.axios) {
                    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
                }
            }
        });

        // ページ遷移成功後に新しい CSRF トークンを取得
        plugin.on("success", (page: any) => {
            const newToken = page.props?.csrf;
            if (newToken) {
                updateGlobalCsrfToken(newToken);
            }
        });

        // エラー時のハンドリング
        plugin.on("error", ({ detail: { response } }: any) => {
            if (response?.status === 419) {
                console.error("[CSRF Error 419]", {
                    path: response.config?.url,
                    currentToken: globalCsrfToken.substring(0, 20) + "...",
                });
                // トークンをリセットして次のリクエストで再取得させる
                globalCsrfToken = "";
            }
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});

// グローバルに CSRF トークン管理オブジェクトを公開（デバッグ用）
(window as any).CsrfManager = {
    getToken: () => globalCsrfToken,
    refreshToken: updateGlobalCsrfToken,
};
