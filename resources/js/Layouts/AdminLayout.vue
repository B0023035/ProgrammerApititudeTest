<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();

const admin = computed(() => page.props.auth?.admin || page.props.admin);

// 現在のルート名を取得
const currentRoute = computed(() => {
    return route().current() as string;
});

// 各タブのアクティブ状態を判定
const isActive = (routeName: string) => {
    return currentRoute.value === routeName;
};

const logout = () => {
    router.post(route("admin.logout"));
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
                        :href="route('admin.results.comlink')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.results.comlink')
                                ? 'border-purple-500 text-purple-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        📊 成績管理(Comlink)
                    </Link>
                    <Link
                        :href="route('admin.results.index')"
                        class="px-3 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                        :class="
                            isActive('admin.results.index')
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
                        "
                    >
                        📈 成績管理
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
                        🎓 学年別一覧
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
