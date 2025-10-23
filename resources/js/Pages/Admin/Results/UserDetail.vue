<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
// import AdminLayout from "@/Layouts/AdminLayout.vue";

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface Session {
    id: number;
    session_uuid: string;
    total_score: number;
    rank: string;
    finished_at: string;
    part1_score?: number;
    part2_score?: number;
    part3_score?: number;
}

interface Props {
    user: User;
    sessions: Session[];
}

const props = defineProps<Props>();

const averageScore = computed(() => {
    if (props.sessions.length === 0) return 0;
    const total = props.sessions.reduce((sum, s) => sum + s.total_score, 0);
    return Math.round((total / props.sessions.length) * 100) / 100;
});

const bestScore = computed(() => {
    if (props.sessions.length === 0) return 0;
    return Math.max(...props.sessions.map((s) => s.total_score));
});

const getRankColor = (rank: string) => {
    const colors: { [key: string]: string } = {
        Platinum: "text-purple-600 bg-purple-100",
        Gold: "text-yellow-600 bg-yellow-100",
        Silver: "text-gray-600 bg-gray-100",
        Bronze: "text-orange-600 bg-orange-100",
    };
    return colors[rank] || "text-gray-600 bg-gray-100";
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${user.name} - ユーザー詳細`" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 戻るボタン -->
                <div class="mb-6">
                    <Link
                        :href="route('admin.results.index')"
                        class="inline-flex items-center text-blue-600 hover:text-blue-800"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        一覧に戻る
                    </Link>
                </div>

                <!-- ユーザー情報カード -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-4">
                            <div
                                class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold"
                            >
                                {{ user.name.charAt(0) }}
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">
                                    {{ user.name }}
                                </h1>
                                <p class="text-gray-600 mt-1">
                                    {{ user.email }}
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    登録日:
                                    {{
                                        new Date(
                                            user.created_at
                                        ).toLocaleDateString("ja-JP")
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">受験回数</div>
                        <div class="text-4xl font-bold">
                            {{ sessions.length }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">回</div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">平均得点</div>
                        <div class="text-4xl font-bold">
                            {{ averageScore }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">点</div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">最高得点</div>
                        <div class="text-4xl font-bold">{{ bestScore }}</div>
                        <div class="text-sm opacity-75 mt-2">点</div>
                    </div>
                </div>

                <!-- 受験履歴 -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-900">
                            受験履歴
                        </h2>
                    </div>

                    <div v-if="sessions.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        受験日時
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        総合得点
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Part1
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Part2
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Part3
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        ランク
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="session in sessions"
                                    :key="session.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{
                                            new Date(
                                                session.finished_at
                                            ).toLocaleString("ja-JP")
                                        }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-lg font-bold text-gray-900"
                                    >
                                        {{ session.total_score }}点
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"
                                    >
                                        {{ session.part1_score || "-" }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"
                                    >
                                        {{ session.part2_score || "-" }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"
                                    >
                                        {{ session.part3_score || "-" }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="getRankColor(session.rank)"
                                            class="px-3 py-1 rounded-full text-sm font-semibold"
                                        >
                                            {{ session.rank }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link
                                            :href="
                                                route(
                                                    'admin.results.session-detail',
                                                    { sessionId: session.id }
                                                )
                                            "
                                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
                                        >
                                            詳細
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="p-12 text-center text-gray-500">
                        まだ受験履歴がありません
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
