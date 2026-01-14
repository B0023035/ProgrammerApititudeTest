<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Session {
    id: number;
    user_id: number;
    session_uuid: string;
    total_score: number;
    total_questions: number;
    rank: string;
    finished_at: string;
    event?: {
        id: number;
        name: string;
    };
    user: {
        id: number;
        name: string;
        email: string;
    };
}

const props = defineProps<{
    sessions?: Session[];
    events?: string[];
}>();

// 検索
const searchQuery = ref("");

// ソート
type SortKey = "user" | "event" | "score" | "rank" | "date";
type SortOrder = "asc" | "desc";
const sortKey = ref<SortKey>("date");
const sortOrder = ref<SortOrder>("desc");

// ページネーション
const currentPage = ref(1);
const perPage = ref(20);

const sessionsList = computed(() => {
    return props.sessions ?? [];
});

// フィルタリング
const filteredSessions = computed(() => {
    let result = sessionsList.value;

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(
            (s: Session) =>
                s.user.name.toLowerCase().includes(q) ||
                s.user.email.toLowerCase().includes(q) ||
                (s.rank && s.rank.toLowerCase().includes(q)) ||
                (s.event?.name && s.event.name.toLowerCase().includes(q))
        );
    }

    return result;
});

// ソート済みセッション
const sortedSessions = computed(() => {
    const result = [...filteredSessions.value];

    result.sort((a, b) => {
        let comparison = 0;

        switch (sortKey.value) {
            case "user":
                comparison = a.user.name.localeCompare(b.user.name, "ja");
                break;
            case "event":
                const eventA = a.event?.name || "";
                const eventB = b.event?.name || "";
                comparison = eventA.localeCompare(eventB, "ja");
                break;
            case "score":
                comparison = a.total_score - b.total_score;
                break;
            case "rank":
                const rankOrder: { [key: string]: number } = {
                    Platinum: 4,
                    Gold: 3,
                    Silver: 2,
                    Bronze: 1,
                };
                comparison = (rankOrder[a.rank] || 0) - (rankOrder[b.rank] || 0);
                break;
            case "date":
                comparison = new Date(a.finished_at).getTime() - new Date(b.finished_at).getTime();
                break;
        }

        return sortOrder.value === "asc" ? comparison : -comparison;
    });

    return result;
});

// ページネーション後のセッション
const paginatedSessions = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return sortedSessions.value.slice(start, end);
});

// 総ページ数
const totalPages = computed(() => {
    return Math.ceil(sortedSessions.value.length / perPage.value) || 1;
});

// ページ変更
const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// ソート変更
const changeSort = (key: SortKey) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortOrder.value = key === "date" ? "desc" : "asc";
    }
    currentPage.value = 1;
};

// ソートアイコン
const getSortIcon = (key: SortKey) => {
    if (sortKey.value !== key) return "↕";
    return sortOrder.value === "asc" ? "↑" : "↓";
};

// 検索時にページをリセット
const onSearch = () => {
    currentPage.value = 1;
};

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

// ページ番号の配列を生成
const pageNumbers = computed(() => {
    const pages: (number | string)[] = [];
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        pages.push(1);

        if (current > 3) {
            pages.push("...");
        }

        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (current < total - 2) {
            pages.push("...");
        }

        pages.push(total);
    }

    return pages;
});
</script>

<template>
    <AdminLayout>
        <Head title="成績管理" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">📊 成績管理</h1>
                    <p class="mt-2 text-gray-600">受験結果の確認と管理</p>
                </div>

                <!-- 統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <p class="text-sm text-gray-600">総セッション数</p>
                        <p class="text-3xl font-bold text-gray-900">
                            {{ sessionsList.length }}
                        </p>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <p class="text-sm text-gray-600">表示中</p>
                        <p class="text-3xl font-bold text-gray-900">
                            {{ filteredSessions.length }}
                        </p>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                        <p class="text-sm text-gray-600">ページ</p>
                        <p class="text-3xl font-bold text-gray-900">
                            {{ currentPage }} / {{ totalPages }}
                        </p>
                    </div>
                </div>

                <!-- 検索・フィルタ -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label
                                for="search"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                検索
                            </label>
                            <input
                                id="search"
                                v-model="searchQuery"
                                @input="onSearch"
                                type="text"
                                placeholder="ユーザー名、メール、イベント名、ランクで検索..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label
                                for="perPage"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                表示件数
                            </label>
                            <select
                                id="perPage"
                                v-model.number="perPage"
                                @change="currentPage = 1"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option :value="10">10件</option>
                                <option :value="20">20件</option>
                                <option :value="50">50件</option>
                                <option :value="100">100件</option>
                            </select>
                        </div>
                        <div>
                            <button
                                @click="
                                    searchQuery = '';
                                    currentPage = 1;
                                "
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                            >
                                クリア
                            </button>
                        </div>
                    </div>
                </div>

                <!-- テーブル -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        @click="changeSort('user')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        ユーザー {{ getSortIcon("user") }}
                                    </th>
                                    <th
                                        @click="changeSort('event')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        イベント {{ getSortIcon("event") }}
                                    </th>
                                    <th
                                        @click="changeSort('score')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        総合得点 {{ getSortIcon("score") }}
                                    </th>
                                    <th
                                        @click="changeSort('rank')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        ランク {{ getSortIcon("rank") }}
                                    </th>
                                    <th
                                        @click="changeSort('date')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        完了日時 {{ getSortIcon("date") }}
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
                                    v-for="session in paginatedSessions"
                                    :key="session.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-blue-600 cursor-pointer hover:text-blue-800"
                                            @click="viewUserDetail(session.user_id)"
                                        >
                                            {{ session.user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ session.user.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ session.event?.name || "—" }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-bold text-gray-900">
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
                                                new Date(session.finished_at).toLocaleString(
                                                    "ja-JP"
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="viewSessionDetail(session.id)"
                                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm"
                                        >
                                            詳細
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div
                            v-if="paginatedSessions.length === 0"
                            class="text-center py-12 text-gray-500"
                        >
                            データがありません
                        </div>
                    </div>

                    <!-- ページネーション -->
                    <div
                        v-if="totalPages > 1"
                        class="bg-gray-50 px-6 py-4 border-t border-gray-200"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                {{ (currentPage - 1) * perPage + 1 }} -
                                {{ Math.min(currentPage * perPage, sortedSessions.length) }} /
                                {{ sortedSessions.length }} 件
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="goToPage(1)"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    «
                                </button>
                                <button
                                    @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    ‹
                                </button>

                                <template v-for="page in pageNumbers" :key="page">
                                    <span v-if="page === '...'" class="px-2 text-gray-500"
                                        >...</span
                                    >
                                    <button
                                        v-else
                                        @click="goToPage(page as number)"
                                        :class="[
                                            'px-3 py-1 rounded border',
                                            currentPage === page
                                                ? 'bg-blue-500 text-white border-blue-500'
                                                : 'border-gray-300 bg-white hover:bg-gray-100',
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                </template>

                                <button
                                    @click="goToPage(currentPage + 1)"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    ›
                                </button>
                                <button
                                    @click="goToPage(totalPages)"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    »
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
