<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";

interface User {
    id: number;
    name: string;
    email: string;
}

interface Session {
    id: number;
    user?: User;
    finished_at: string;
}

interface Admin {
    name: string;
    email: string;
}

const props = defineProps<{
    recentSessions?: Session[];
    recentUsers?: User[];
    auth?: {
        user: Admin | null;
    };
}>();

const userName = computed(() => {
    return props.auth?.user?.name || "管理者";
});

const logout = () => {
    router.post(route("admin.logout"));
};
</script>

<template>
    <Head title="管理者ダッシュボード" />

    <div class="min-h-screen bg-gray-100">
        <!-- ヘッダー -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <img src="/images/YIC_logo.png" alt="YIC Logo" class="h-10" />
                        <h1 class="text-2xl font-bold text-gray-900">管理者ダッシュボード</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600"> {{ userName }} さん </span>
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

        <!-- メインコンテンツ -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- メニューカード -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- イベント管理(新規) -->
                <Link
                    :href="route('admin.events.index')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors"
                        >
                            🎫 イベント管理
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-indigo-600 transition-colors"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                    <p class="text-gray-600 text-sm">セッションコードの作成・管理</p>
                </Link>

                <!-- Comlink成績管理システム -->
                <Link
                    :href="route('admin.results.comlink')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-purple-600 transition-colors"
                        >
                            📊 成績管理 (Comlink)
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-purple-600 transition-colors"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                    <p class="text-gray-600 text-sm">Web Workerを活用した高速成績分析システム</p>
                </Link>

                <!-- 成績管理を Comlink に統一 -->
                <Link
                    :href="route('admin.results.comlink')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-purple-600 transition-colors"
                        >
                            📊 成績管理
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-purple-600 transition-colors"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                    <p class="text-gray-600 text-sm">受験結果の確認と管理 (Comlink)</p>
                </Link>

                <!-- 統計・グラフ -->
                <Link
                    :href="route('admin.results.statistics')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-green-600 transition-colors"
                        >
                            📊 統計・グラフ
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-green-600 transition-colors"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                    <p class="text-gray-600 text-sm">詳細な統計データとグラフ表示</p>
                </Link>

                <!-- 学年別一覧 -->
                <Link
                    :href="route('admin.results.grade-list')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors"
                        >
                            🎓 学年別一覧
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-indigo-600 transition-colors"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                    <p class="text-gray-600 text-sm">学年ごとの成績データ</p>
                </Link>

                <!-- ユーザー管理 -->
                <Link
                    :href="route('admin.users.index')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-purple-600 transition-colors"
                        >
                            👥 ユーザー管理
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-purple-600 transition-colors"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </div>
                    <p class="text-gray-600 text-sm">ユーザーアカウントの管理</p>
                </Link>
            </div>

            <!-- 最近のセッション表示を削除しました -->
        </main>
    </div>
</template>
