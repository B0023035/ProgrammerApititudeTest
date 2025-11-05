<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";

interface Stats {
    total_users: number;
    total_sessions: number;
    active_sessions?: number;
}

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
    stats: Stats;
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
            <!-- 統計カード -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-1">総ユーザー数</p>
                            <p class="text-4xl font-bold">
                                {{ stats.total_users }}
                            </p>
                        </div>
                        <svg
                            class="w-12 h-12 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-1">完了セッション</p>
                            <p class="text-4xl font-bold">
                                {{ stats.total_sessions }}
                            </p>
                        </div>
                        <svg
                            class="w-12 h-12 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90 mb-1">アクティブセッション</p>
                            <p class="text-4xl font-bold">
                                {{ stats.active_sessions || 0 }}
                            </p>
                        </div>
                        <svg
                            class="w-12 h-12 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- メニューカード -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- イベント管理（新規） -->
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

                <!-- 通常の成績管理 -->
                <Link
                    :href="route('admin.results.index')"
                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all transform hover:scale-105 cursor-pointer group"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors"
                        >
                            📈 成績管理
                        </h3>
                        <svg
                            class="w-6 h-6 text-gray-400 group-hover:text-blue-600 transition-colors"
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
                    <p class="text-gray-600 text-sm">受験結果の確認と管理</p>
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

            <!-- 最近のセッション -->
            <div
                v-if="recentSessions && recentSessions.length > 0"
                class="mt-8 bg-white rounded-xl shadow-lg p-6"
            >
                <h3 class="text-xl font-bold text-gray-900 mb-4">最近の受験セッション</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    ユーザー
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    完了日時
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    ステータス
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="session in recentSessions"
                                :key="session.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ session.user?.name || "不明" }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(session.finished_at).toLocaleString("ja-JP") }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span
                                        class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold"
                                    >
                                        完了
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</template>
