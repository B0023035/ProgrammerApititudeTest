<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Session {
    id: number;
    user_id: number;
    session_uuid: string;
    total_score: number;
    rank: string;
    finished_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    sessions: {
        data: Session[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const props = defineProps<Props>();

const searchQuery = ref("");

const filteredSessions = computed(() => {
    if (!searchQuery.value) {
        return props.sessions.data;
    }

    const query = searchQuery.value.toLowerCase();
    return props.sessions.data.filter(
        (session) =>
            session.user.name.toLowerCase().includes(query) ||
            session.user.email.toLowerCase().includes(query) ||
            session.rank.toLowerCase().includes(query)
    );
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

const viewSessionDetail = (sessionId: number) => {
    router.visit(route("admin.results.session-detail", { sessionId }));
};

const viewUserDetail = (userId: number) => {
    router.visit(route("admin.results.user-detail", { userId }));
};
</script>

<template>
    <AdminLayout>
        <Head title="成績管理 - 一覧" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">
                        📊 成績管理 - 受験セッション一覧
                    </h1>
                    <p class="mt-2 text-gray-600">
                        全ての受験セッションを確認できます
                    </p>
                </div>

                <!-- 統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div
                        class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">
                                    総セッション数
                                </p>
                                <p class="text-3xl font-bold text-gray-900">
                                    {{ sessions.total }}
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <svg
                                    class="w-8 h-8 text-blue-600"
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
                    </div>

                    <div
                        class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">表示中</p>
                                <p class="text-3xl font-bold text-gray-900">
                                    {{ filteredSessions.length }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <svg
                                    class="w-8 h-8 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">
                                    現在のページ
                                </p>
                                <p class="text-3xl font-bold text-gray-900">
                                    {{ sessions.current_page }} /
                                    {{ sessions.last_page }}
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <svg
                                    class="w-8 h-8 text-purple-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 検索フィルター -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label
                                for="search"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                検索
                            </label>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="ユーザー名、メールアドレス、ランクで検索..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div class="pt-7">
                            <button
                                @click="searchQuery = ''"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                            >
                                クリア
                            </button>
                        </div>
                    </div>
                </div>

                <!-- セッション一覧テーブル -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                        総合得点
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        ランク
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        完了日時
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
                                    v-for="session in filteredSessions"
                                    :key="session.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-blue-600 cursor-pointer hover:text-blue-800"
                                            @click="
                                                viewUserDetail(session.user_id)
                                            "
                                        >
                                            {{ session.user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ session.user.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
                                            {{ session.total_score }}点
                                        </div>
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
                                        <div class="text-sm text-gray-900">
                                            {{
                                                new Date(
                                                    session.finished_at
                                                ).toLocaleString("ja-JP")
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="
                                                viewSessionDetail(session.id)
                                            "
                                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
                                        >
                                            詳細を見る
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div
                            v-if="filteredSessions.length === 0"
                            class="text-center py-12 text-gray-500"
                        >
                            データがありません
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
