<script setup lang="ts">
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";

const page = usePage();
const isProcessing = ref(false);
const isReady = ref(false);

// CSRFトークンを強制的に更新する関数
const refreshCsrfToken = async (): Promise<boolean> => {
    try {
        // まずCSRFクッキーを更新
        await fetch("/sanctum/csrf-cookie", {
            method: "GET",
            credentials: "same-origin",
        });

        // 新しいトークンを取得
        const response = await fetch("/csrf-token", {
            method: "GET",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.token) {
                // metaタグを更新
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    meta.setAttribute("content", data.token);
                }
                // axiosヘッダーも更新
                if ((window as any).axios) {
                    (window as any).axios.defaults.headers.common["X-CSRF-TOKEN"] = data.token;
                }
                return true;
            }
        }
        return false;
    } catch (error) {
        console.error("CSRF token refresh error:", error);
        return false;
    }
};

const goBack = async () => {
    // 二重クリック防止
    if (isProcessing.value) {
        return;
    }
    isProcessing.value = true;

    try {
        // ★★★ CSRFトークンを事前に更新 ★★★
        await refreshCsrfToken();

        // Inertia routerを使用してセッションクリアを実行
        router.post(
            "/session/clear",
            {},
            {
                preserveState: false,
                preserveScroll: false,
                onSuccess: () => {
                    // 成功時は自動的にリダイレクトされる
                },
                onError: () => {
                    // エラー時も強制的にトップページへ
                    window.location.href = "/";
                },
                onFinish: () => {
                    isProcessing.value = false;
                },
            }
        );
    } catch (error) {
        console.error("Session clear error:", error);
        // エラーが出ても強制的に戻る
        window.location.href = "/";
        isProcessing.value = false;
    }
};

onMounted(async () => {
    // ★★★ ページ読み込み時にCSRFトークンを強制更新 ★★★
    await refreshCsrfToken();
    isReady.value = true;

    const currentPath = window.location.pathname;

    // Welcomeページ(/welcome)以外ではリダイレクトしない
    if (currentPath !== "/welcome") {
        return;
    }

    // 一般ユーザーがログインしている場合のみリダイレクト
    // 管理者ユーザーはリダイレクトしない（isAdminがfalseの場合のみ）
    if (page.props.auth?.user && !page.props.auth?.isAdmin) {
        router.visit("/test-start");
    }
});
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen main_contents bg-gray-100">
        <!-- ロゴ表示 -->
        <div class="bg-gray-100 logo-container">
            <img src="/images/YIC_logo.png" class="YIC_logo" />
        </div>

        <!-- タイトル -->
        <div>
            <h1 class="custom-title bg-gray-100">プログラマー適性検査</h1>
        </div>

        <!-- 未ログイン用リンク -->
        <div
            class="bg-gray-100 flex flex-col sm:justify-center items-center pt-20 sm:pt-20 space-y-4"
        >
            <Link href="/login" class="btn-link"> Log in </Link>
            <Link href="/register" class="btn-link"> Register </Link>
            <Link href="/guest/info" class="btn-link"> Guest </Link>
        </div>

        <!-- 戻るボタン -->
        <div class="back-button-container">
            <button
                @click="goBack"
                :disabled="!isReady || isProcessing"
                class="back-button"
                :class="{ 'opacity-50 cursor-not-allowed': !isReady || isProcessing }"
            >
                <span v-if="!isReady">準備中...</span>
                <span v-else-if="isProcessing">処理中...</span>
                <span v-else>← 戻る</span>
            </button>
        </div>
    </div>

    <!-- フッター -->
    <footer class="bg-gray-800 text-white text-center py-4">
        © 2025 YIC Group. All rights reserved.
    </footer>
</template>

<style scoped>
.logo-container {
    padding-top: 10px;
    padding-left: 15px;
}

.YIC_logo {
    width: 403px;
    height: 51px;
}

.custom-title {
    padding-top: 100px;
    font-family: "Yu Mincho Demibold", "游明朝 Demibold", "YuMincho", serif;
    font-size: 50px;
    text-align: center;
}

.main_contents {
    min-height: calc(100vh - 64px);
    display: flex;
    flex-direction: column;
}

.btn-link {
    display: inline-block;
    width: 240px;
    padding: 16px 32px;
    font-size: 18px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    color: white;
    background-color: #2563eb;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.btn-link:hover {
    background-color: #1d4ed8;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.back-button-container {
    position: fixed;
    bottom: 80px;
    left: 20px;
}

.back-button {
    display: inline-block;
    padding: 10px 20px;
    font-size: 16px;
    font-weight: 500;
    text-decoration: none;
    color: #4b5563;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.back-button:hover {
    background-color: #f9fafb;
    border-color: #9ca3af;
    color: #1f2937;
}
</style>
