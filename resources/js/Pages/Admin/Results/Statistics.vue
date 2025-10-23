<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
// import AdminResultsTabs from "@/Components/AdminResultsTabs.vue";

interface Stats {
    total_sessions: number;
    total_users: number;
    average_score: number;
}

interface Props {
    stats: Stats;
}

const props = defineProps<Props>();
</script>

<template>
    <AdminLayout>
        <Head title="統計・グラフ" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">
                        📊 統計・グラフ
                    </h1>
                    <p class="mt-2 text-gray-600">
                        全体の統計情報とグラフを確認できます
                    </p>
                </div>

                <!-- タブナビゲーション -->
                <AdminResultsTabs />

                <!-- 主要統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-8 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-2">
                                    総セッション数
                                </p>
                                <p class="text-5xl font-bold">
                                    {{ stats.total_sessions }}
                                </p>
                                <p class="text-sm opacity-75 mt-2">
                                    セッション
                                </p>
                            </div>
                            <svg
                                class="w-16 h-16 opacity-50"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-8 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-2">
                                    登録ユーザー数
                                </p>
                                <p class="text-5xl font-bold">
                                    {{ stats.total_users }}
                                </p>
                                <p class="text-sm opacity-75 mt-2">ユーザー</p>
                            </div>
                            <svg
                                class="w-16 h-16 opacity-50"
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
                        class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-8 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-2">平均得点</p>
                                <p class="text-5xl font-bold">
                                    {{ stats.average_score }}
                                </p>
                                <p class="text-sm opacity-75 mt-2">点</p>
                            </div>
                            <svg
                                class="w-16 h-16 opacity-50"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- グラフエリア(プレースホルダー) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- ランク分布 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            ランク分布
                        </h2>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    Platinum
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-purple-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 15%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >15%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    Gold
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-yellow-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 25%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >25%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    Silver
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-gray-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 35%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >35%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    Bronze
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-orange-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 25%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >25%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 得点分布 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            得点分布
                        </h2>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    90-100点
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-green-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 20%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >20%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    80-89点
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-blue-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 30%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >30%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    70-79点
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-yellow-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 25%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >25%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    60-69点
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-orange-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 15%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >15%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-24 text-sm font-medium text-gray-700"
                                >
                                    60点未満
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-red-500 h-6 rounded-full flex items-center justify-end pr-2"
                                            style="width: 10%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >10%</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Part別平均点 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            Part別平均点
                        </h2>
                        <div class="space-y-6">
                            <div>
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700"
                                        >Part 1 - 規則発見力</span
                                    >
                                    <span
                                        class="text-lg font-bold text-blue-600"
                                        >72.5点</span
                                    >
                                </div>
                                <div class="bg-gray-200 rounded-full h-4">
                                    <div
                                        class="bg-blue-500 h-4 rounded-full"
                                        style="width: 72.5%"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700"
                                        >Part 2 - 空間把握力</span
                                    >
                                    <span
                                        class="text-lg font-bold text-green-600"
                                        >68.3点</span
                                    >
                                </div>
                                <div class="bg-gray-200 rounded-full h-4">
                                    <div
                                        class="bg-green-500 h-4 rounded-full"
                                        style="width: 68.3%"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700"
                                        >Part 3 - 問題解決力</span
                                    >
                                    <span
                                        class="text-lg font-bold text-purple-600"
                                        >75.8点</span
                                    >
                                </div>
                                <div class="bg-gray-200 rounded-full h-4">
                                    <div
                                        class="bg-purple-500 h-4 rounded-full"
                                        style="width: 75.8%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 月別受験者数 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            月別受験者数(2025年)
                        </h2>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div
                                    class="w-16 text-sm font-medium text-gray-700"
                                >
                                    1月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2"
                                            style="width: 45%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >45</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-16 text-sm font-medium text-gray-700"
                                >
                                    2月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2"
                                            style="width: 52%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >52</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-16 text-sm font-medium text-gray-700"
                                >
                                    3月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2"
                                            style="width: 38%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >38</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-16 text-sm font-medium text-gray-700"
                                >
                                    4月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2"
                                            style="width: 67%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >67</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-16 text-sm font-medium text-gray-700"
                                >
                                    5月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2"
                                            style="width: 71%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >71</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div
                                    class="w-16 text-sm font-medium text-gray-700"
                                >
                                    6月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2"
                                            style="width: 58%"
                                        >
                                            <span
                                                class="text-xs text-white font-semibold"
                                                >58</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 補足情報 -->
                <div
                    class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6"
                >
                    <div class="flex items-start">
                        <svg
                            class="w-6 h-6 text-blue-600 mt-1 mr-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <div>
                            <h3
                                class="text-lg font-semibold text-blue-900 mb-2"
                            >
                                統計情報について
                            </h3>
                            <p class="text-sm text-blue-800">
                                上記のグラフはサンプルデータを使用しています。実際のデータに基づいたグラフを表示するには、Recharts、Chart.js、D3.jsなどのライブラリを使用してください。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
