import axios from "axios";
window.axios = axios;

// ★ 重要: credentialsを含めることでセッションCookieがリクエストに含まれる
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// CSRF トークンをメタタグから取得してデフォルトヘッダーに設定
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
};

let token = getCsrfToken();
if (token) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
    console.log("[CSRF] Initial token set:", token.substring(0, 20) + "...");
}

// リクエスト前に CSRF トークンを確認・更新
window.axios.interceptors.request.use(
    config => {
        const currentToken = getCsrfToken();
        if (currentToken && currentToken !== token) {
            console.log("[CSRF] Token updated from meta tag");
            token = currentToken;
            config.headers["X-CSRF-TOKEN"] = currentToken;
        }
        return config;
    },
    error => Promise.reject(error)
);

// レスポンス受信後に CSRF トークンを更新（サーバーが新しいトークンを返した場合）
window.axios.interceptors.response.use(
    response => {
        // レスポンスヘッダーに新しい CSRF トークンがあれば更新
        const newToken = response.headers["x-csrf-token"];
        if (newToken && newToken !== token) {
            console.log(
                "[CSRF] New token from response header:",
                newToken.substring(0, 20) + "..."
            );
            token = newToken;
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                csrfMeta.setAttribute("content", newToken);
            }
            window.axios.defaults.headers.common["X-CSRF-TOKEN"] = newToken;
        }
        return response;
    },
    error => {
        // 419 エラーの詳細をログ出力
        if (error.response?.status === 419) {
            console.error("[CSRF 419 ERROR]", {
                url: error.response.config.url,
                method: error.response.config.method,
                currentToken: token.substring(0, 20) + "...",
                metaToken: getCsrfToken().substring(0, 20) + "...",
                headers: error.response.config.headers,
            });
        }
        return Promise.reject(error);
    }
);
