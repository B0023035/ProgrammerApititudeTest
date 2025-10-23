<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

const isActive = (routeName: string) => {
    // 現在のルート名を取得
    const currentRoute = route().current();

    // 完全一致でチェック
    return currentRoute === routeName;
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-lg mb-6 overflow-hidden">
        <div class="flex border-b border-gray-200">
            <Link
                :href="route('admin.results.comlink')"
                :class="[
                    'flex-1 px-6 py-4 text-center font-medium transition-colors',
                    isActive('admin.results.comlink')
                        ? 'border-b-2 border-purple-500 text-purple-600 bg-purple-50'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                ]"
            >
                📊 Comlink
            </Link>
            <Link
                :href="route('admin.results.index')"
                :class="[
                    'flex-1 px-6 py-4 text-center font-medium transition-colors',
                    isActive('admin.results.index')
                        ? 'border-b-2 border-blue-500 text-blue-600 bg-blue-50'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                ]"
            >
                📈 成績管理
            </Link>
            <Link
                :href="route('admin.results.statistics')"
                :class="[
                    'flex-1 px-6 py-4 text-center font-medium transition-colors',
                    isActive('admin.results.statistics')
                        ? 'border-b-2 border-green-500 text-green-600 bg-green-50'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                ]"
            >
                📊 統計・グラフ
            </Link>
            <Link
                :href="route('admin.results.grade-list')"
                :class="[
                    'flex-1 px-6 py-4 text-center font-medium transition-colors',
                    isActive('admin.results.grade-list')
                        ? 'border-b-2 border-indigo-500 text-indigo-600 bg-indigo-50'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                ]"
            >
                🎓 学年別一覧
            </Link>
        </div>
    </div>
</template>
