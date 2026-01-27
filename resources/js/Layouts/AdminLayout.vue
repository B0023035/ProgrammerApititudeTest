<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();

const admin = computed(() => page.props.auth?.user);

// 現在のルート名を取得（安全に呼び出す）
const currentRoute = computed(() => {
    try {
        if (typeof route === "function" && route().current) {
            const r = route().current();
            return typeof r === "string" ? r : String(r || "");
        }
    } catch (e) {
        // route() が未定義やエラーの場合は空文字を返す
        console.debug && console.debug("route() access error in AdminLayout:", e);
    }
    return "";
});

// 各タブのアクティブ状態を判定
const isActive = (routeName: string) => {
    try {
        return currentRoute.value === routeName;
    } catch (e) {
        return false;
    }
};

// ★ hidden form でログアウト（セッション cookie を確実に含める）
const logout = () => {
    const form_element = document.createElement("form");
    form_element.method = "POST";
    form_element.action = route("admin.logout");
    form_element.style.display = "none";

    // CSRF トークンを追加
    const tokenInput = document.createElement("input");
    tokenInput.type = "hidden";
    tokenInput.name = "_token";
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    tokenInput.value = csrfToken;
    form_element.appendChild(tokenInput);

    document.body.appendChild(form_element);
    form_element.submit();
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- ヘッダー -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link :href="route('admin.dashboard')">
                            <img
                                src="/images/YIC_logo.png"
                                alt="YIC Logo"
                                class="h-10 cursor-pointer hover:opacity-80 transition-opacity"
                            />
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">管理者ダッシュボード</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            {{ admin?.name || "管理者" }} さん
                        </span>
                        <button
                            @click="logout"
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                        >
                            ログアウト
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- ナビゲーション -->
        <nav class="bg-white shadow-sm border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex space-x-8 overflow-x-auto">
                    <Link
                        :href="route('admin.dashboard')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.dashboard')
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        🏠 ダッシュボード
                    </Link>
                    <Link
                        :href="route('admin.events.index')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.events.index')
                                ? 'border-pink-500 text-pink-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        🎫 イベント管理
                    </Link>
                    <Link
                        :href="route('admin.results.comlink')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.results.comlink')
                                ? 'border-purple-500 text-purple-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        📊 成績管理
                    </Link>
                    <Link
                        :href="route('admin.results.statistics')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.results.statistics')
                                ? 'border-green-500 text-green-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        📊 統計・グラフ
                    </Link>
                    <Link
                        :href="route('admin.results.grade-list')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.results.grade-list')
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        🎓 卒業年度別一覧
                    </Link>
                    <Link
                        :href="route('admin.users.index')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.users.index')
                                ? 'border-purple-500 text-purple-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        👥 ユーザー管理
                    </Link>
                    <Link
                        :href="route('admin.admins.index')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.admins.index')
                                ? 'border-purple-500 text-purple-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        🔐 管理者アカウント
                    </Link>
                </div>
            </div>
        </nav>

        <!-- メインコンテンツ -->
        <main class="min-h-screen">
            <slot />
        </main>

        <!-- フッター -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <p class="text-center text-gray-500 text-sm">
                    © 2025 YIC Group. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
