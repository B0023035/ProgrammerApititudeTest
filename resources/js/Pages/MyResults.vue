<script setup lang="ts">
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

interface PartResult {
    correct: number;
    incorrect: number;
    unanswered: number;
    total: number;
    score: number;
}

interface ExamResult {
    id: number;
    session_uuid: string;
    event_name: string;
    finished_at: string;
    total_score: number;
    max_score: number;
    rank: string;
    rank_name: string;
    part_results: {
        1: PartResult;
        2: PartResult;
        3: PartResult;
    };
}

const props = defineProps<{
    results: ExamResult[];
    events: string[];
}>();

// 検索
const searchQuery = ref("");

// イベント絞り込み
const selectedEvent = ref("");

// ソート
type SortKey = "event" | "score" | "rank" | "date";
type SortOrder = "asc" | "desc";
const sortKey = ref<SortKey>("date");
const sortOrder = ref<SortOrder>("desc");

// ページネーション
const currentPage = ref(1);
const perPage = ref(10);

// フィルタリング
const filteredResults = computed(() => {
    let result = props.results;

    if (selectedEvent.value) {
        result = result.filter(r => r.event_name === selectedEvent.value);
    }

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(
            r => r.event_name.toLowerCase().includes(q) || r.rank_name.toLowerCase().includes(q)
        );
    }

    return result;
});

// ソート済み結果
const sortedResults = computed(() => {
    const result = [...filteredResults.value];

    result.sort((a, b) => {
        let comparison = 0;

        switch (sortKey.value) {
            case "event":
                comparison = a.event_name.localeCompare(b.event_name, "ja");
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
                comparison = (rankOrder[a.rank_name] || 0) - (rankOrder[b.rank_name] || 0);
                break;
            case "date":
                comparison = new Date(a.finished_at).getTime() - new Date(b.finished_at).getTime();
                break;
        }

        return sortOrder.value === "asc" ? comparison : -comparison;
    });

    return result;
});

// ページネーション後の結果
const paginatedResults = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return sortedResults.value.slice(start, end);
});

// 総ページ数
const totalPages = computed(() => {
    return Math.ceil(sortedResults.value.length / perPage.value) || 1;
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

// フィルター変更時にページをリセット
const onFilterChange = () => {
    currentPage.value = 1;
};

// ランクの色
const getRankColor = (rank: string) => {
    const colors: { [key: string]: string } = {
        Platinum: "text-purple-600 bg-purple-100",
        Gold: "text-yellow-600 bg-yellow-100",
        Silver: "text-gray-600 bg-gray-100",
        Bronze: "text-orange-600 bg-orange-100",
    };
    return colors[rank] || "text-gray-600 bg-gray-100";
};

// 詳細ページへ移動
const viewDetail = (sessionId: number) => {
    router.visit(route("my-results.detail", { sessionId }));
};

// ページ番号の配列
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
        if (current > 3) pages.push("...");
        const start = Math.max(2, current - 1);
        const end = Math.min(total - 1, current + 1);
        for (let i = start; i <= end; i++) pages.push(i);
        if (current < total - 2) pages.push("...");
        pages.push(total);
    }

    return pages;
});

// フィルターをクリア
const clearFilters = () => {
    searchQuery.value = "";
    selectedEvent.value = "";
    currentPage.value = 1;
};
</script>

<template>
    <Head title="過去の試験結果" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">過去の試験結果</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <p class="text-sm text-gray-600">総受験回数</p>
                        <p class="text-3xl font-bold text-gray-900">{{ results.length }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <p class="text-sm text-gray-600">表示中</p>
                        <p class="text-3xl font-bold text-gray-900">{{ filteredResults.length }}</p>
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
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2"
                                >検索</label
                            >
                            <input
                                id="search"
                                v-model="searchQuery"
                                @input="onFilterChange"
                                type="text"
                                placeholder="イベント名、ランクで検索..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div class="min-w-[180px]">
                            <label
                                for="eventFilter"
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >イベント</label
                            >
                            <select
                                id="eventFilter"
                                v-model="selectedEvent"
                                @change="onFilterChange"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">すべて</option>
                                <option v-for="event in events" :key="event" :value="event">
                                    {{ event }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                for="perPage"
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >表示件数</label
                            >
                            <select
                                id="perPage"
                                v-model.number="perPage"
                                @change="currentPage = 1"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                                <option :value="10">10件</option>
                                <option :value="20">20件</option>
                                <option :value="50">50件</option>
                            </select>
                        </div>
                        <div>
                            <button
                                @click="clearFilters"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600"
                            >
                                クリア
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 結果がない場合 -->
                <div
                    v-if="results.length === 0"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                >
                    <div class="p-12 text-center">
                        <h3 class="mt-4 text-lg font-medium text-gray-900">試験結果がありません</h3>
                        <p class="mt-2 text-gray-500">
                            まだ試験を受けていないか、完了した試験がありません。
                        </p>
                        <div class="mt-6">
                            <Link
                                :href="route('test.start')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 rounded-md text-sm text-white hover:bg-blue-700"
                            >
                                試験を受ける
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- テーブル -->
                <div v-else class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        @click="changeSort('event')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                    >
                                        イベント {{ getSortIcon("event") }}
                                    </th>
                                    <th
                                        @click="changeSort('score')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                    >
                                        総合得点 {{ getSortIcon("score") }}
                                    </th>
                                    <th
                                        @click="changeSort('rank')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                    >
                                        ランク {{ getSortIcon("rank") }}
                                    </th>
                                    <th
                                        @click="changeSort('date')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100"
                                    >
                                        完了日時 {{ getSortIcon("date") }}
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="result in paginatedResults"
                                    :key="result.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ result.event_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-bold text-gray-900">
                                            {{ result.total_score }}点
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            / {{ result.max_score }}点満点
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="getRankColor(result.rank_name)"
                                            class="px-3 py-1 rounded-full text-sm font-semibold"
                                        >
                                            {{ result.rank_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ new Date(result.finished_at).toLocaleString("ja-JP") }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button
                                            @click="viewDetail(result.id)"
                                            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"
                                        >
                                            詳細
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div
                            v-if="paginatedResults.length === 0"
                            class="text-center py-12 text-gray-500"
                        >
                            条件に一致するデータがありません
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
                                {{ Math.min(currentPage * perPage, sortedResults.length) }} /
                                {{ sortedResults.length }} 件
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="goToPage(1)"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50"
                                >
                                    «
                                </button>
                                <button
                                    @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50"
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
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50"
                                >
                                    ›
                                </button>
                                <button
                                    @click="goToPage(totalPages)"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-50"
                                >
                                    »
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 戻るボタン -->
                <div class="mt-8 text-center">
                    <Link
                        :href="route('test.start')"
                        class="inline-flex items-center px-6 py-3 bg-gray-600 rounded-md text-sm text-white hover:bg-gray-700"
                    >
                        ← テスト開始画面に戻る
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
