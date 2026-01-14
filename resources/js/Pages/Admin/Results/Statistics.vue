<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { computed, ref, watch } from "vue";

interface RankDistribution {
    Platinum: number;
    Gold: number;
    Silver: number;
    Bronze: number;
}

interface PartAverageInfo {
    average: number;
    question_count: number;
    min_score: number;
    max_score: number;
}

interface PartAverages {
    [key: number]: PartAverageInfo;
}

interface MonthlyData {
    [key: number]: number;
}

interface Stats {
    total_sessions: number;
    total_users: number;
    average_score: number;
    rank_distribution: RankDistribution;
    part_averages: PartAverages;
    monthly_data: MonthlyData;
}

interface Filters {
    grade?: string | number | null;
    event_id?: number | string | null;
}

interface GradeCount {
    grade: number;
    label: string;
    count: number;
}

interface PropsWithFilters {
    stats: Stats;
    filters?: Filters;
    events?: Array<{ id: number; label: string }>;
    gradeCounts?: GradeCount[];
}

const { stats, filters, events, gradeCounts } = defineProps<PropsWithFilters>();

// 学年選択肢を生成(データが存在する grade のみを表示)
const gradeOptions = computed(() => {
    if (!gradeCounts || gradeCounts.length === 0) {
        console.warn("gradeCounts is empty or undefined");
        return [];
    }

    return gradeCounts.map(gc => ({
        value: String(gc.grade),
        label: gc.label,
    }));
});

// フィルターの状態を管理 - 初期値をfiltersから設定
const getInitialGrade = () => {
    if (filters?.grade !== null && filters?.grade !== undefined && filters?.grade !== "") {
        const gradeValue = String(filters.grade);
        // gradeCountsから直接チェック（computedプロパティの前に実行されるため）
        const gradeExists = gradeCounts && gradeCounts.some(gc => String(gc.grade) === gradeValue);
        if (gradeExists) {
            return gradeValue;
        }
    }
    return "all";
};

const getInitialEventId = () => {
    if (filters?.event_id) {
        return String(filters.event_id);
    }
    return "";
};

const selectedGrade = ref<string>(getInitialGrade());
const selectedEventId = ref<string>(getInitialEventId());

const rankPercentages = computed(() => {
    const total = stats.total_sessions;
    if (total === 0 || !stats.rank_distribution) {
        return { Platinum: 0, Gold: 0, Silver: 0, Bronze: 0 };
    }

    return {
        Platinum: Math.round((stats.rank_distribution.Platinum / total) * 100),
        Gold: Math.round((stats.rank_distribution.Gold / total) * 100),
        Silver: Math.round((stats.rank_distribution.Silver / total) * 100),
        Bronze: Math.round((stats.rank_distribution.Bronze / total) * 100),
    };
});

// 月別データの最大値を取得(グラフのスケール用)
const maxMonthlyCount = computed(() => {
    if (!stats.monthly_data) return 1;
    return Math.max(...(Object.values(stats.monthly_data) as number[]), 1);
});

// パート別情報を取得するヘルパー
const getPartInfo = (part: number): PartAverageInfo => {
    const info = stats.part_averages?.[part];
    if (info && typeof info === 'object') {
        return info;
    }
    // フォールバック（古い形式の場合）
    return {
        average: typeof info === 'number' ? info : 0,
        question_count: part === 1 ? 40 : part === 2 ? 30 : 25,
        min_score: part === 1 ? -10 : part === 2 ? -7.5 : -6.25,
        max_score: part === 1 ? 40 : part === 2 ? 30 : 25,
    };
};

// Part別平均点のグラフ幅を計算（動的な問題数に対応）
const getPartBarWidth = (part: number, score: number) => {
    const info = getPartInfo(part);
    const range = info.max_score - info.min_score;
    if (range === 0) return 0;
    const adjusted = score - info.min_score;
    return Math.max(0, Math.min(100, (adjusted / range) * 100));
};

// 0点の位置を計算（グラフ上のゼロライン表示用）
const getZeroLinePosition = (part: number) => {
    const info = getPartInfo(part);
    const range = info.max_score - info.min_score;
    if (range === 0) return 0;
    return ((0 - info.min_score) / range) * 100;
};
</script>

<template>
    <AdminLayout>
        <Head title="統計・グラフ" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">📊 統計・グラフ</h1>
                    <p class="mt-2 text-gray-600">全体の統計情報とグラフを確認できます</p>
                </div>

                <!-- タブナビゲーション -->
                <AdminResultsTabs />

                <!-- フィルター(学年は1-3年に制限、セッション選択で絞り込み) -->
                <form method="get" class="mt-4 mb-6 flex flex-wrap gap-3 items-end">
                    <div class="w-36">
                        <label class="block text-sm font-medium text-gray-700">学年</label>
                        <select
                            name="grade"
                            v-model="selectedGrade"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="all">すべて</option>
                            <option v-for="opt in gradeOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <div class="w-96">
                        <label class="block text-sm font-medium text-gray-700">イベント</label>
                        <select
                            name="event_id"
                            v-model="selectedEventId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">(指定なし)</option>
                            <option v-for="e in events ?? []" :key="e.id" :value="e.id">
                                {{ e.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            絞り込む
                        </button>
                    </div>
                </form>

                <!-- 主要統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-8 text-white transform hover:scale-105 transition-transform"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-2">総セッション数</p>
                                <p class="text-5xl font-bold">
                                    {{ stats.total_sessions }}
                                </p>
                                <p class="text-sm opacity-75 mt-2">セッション</p>
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
                        class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-8 text-white transform hover:scale-105 transition-transform"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-2">登録ユーザー数</p>
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
                        class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-8 text-white transform hover:scale-105 transition-transform"
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

                <!-- グラフエリア -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- ランク分布 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">ランク分布</h2>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div class="w-24 text-sm font-medium text-gray-700">Platinum</div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-purple-500 h-6 rounded-full flex items-center justify-end pr-2 transition-all"
                                            :style="`width: ${rankPercentages.Platinum}%`"
                                        >
                                            <span
                                                v-if="rankPercentages.Platinum > 0"
                                                class="text-xs text-white font-semibold"
                                            >
                                                {{ stats.rank_distribution?.Platinum || 0 }}人 ({{
                                                    rankPercentages.Platinum
                                                }}%)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-24 text-sm font-medium text-gray-700">Gold</div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-yellow-500 h-6 rounded-full flex items-center justify-end pr-2 transition-all"
                                            :style="`width: ${rankPercentages.Gold}%`"
                                        >
                                            <span
                                                v-if="rankPercentages.Gold > 0"
                                                class="text-xs text-white font-semibold"
                                            >
                                                {{ stats.rank_distribution.Gold }}人 ({{
                                                    rankPercentages.Gold
                                                }}%)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-24 text-sm font-medium text-gray-700">Silver</div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-gray-500 h-6 rounded-full flex items-center justify-end pr-2 transition-all"
                                            :style="`width: ${rankPercentages.Silver}%`"
                                        >
                                            <span
                                                v-if="rankPercentages.Silver > 0"
                                                class="text-xs text-white font-semibold"
                                            >
                                                {{ stats.rank_distribution.Silver }}人 ({{
                                                    rankPercentages.Silver
                                                }}%)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-24 text-sm font-medium text-gray-700">Bronze</div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-6">
                                        <div
                                            class="bg-orange-500 h-6 rounded-full flex items-center justify-end pr-2 transition-all"
                                            :style="`width: ${rankPercentages.Bronze}%`"
                                        >
                                            <span
                                                v-if="rankPercentages.Bronze > 0"
                                                class="text-xs text-white font-semibold"
                                            >
                                                {{ stats.rank_distribution.Bronze }}人 ({{
                                                    rankPercentages.Bronze
                                                }}%)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Part別平均点 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            Part別平均点 (マイナス点あり)
                        </h2>
                        <div class="space-y-6">
                            <!-- Part 1 -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700"
                                        >Part 1 - 規則発見力 ({{ getPartInfo(1).min_score }}〜{{ getPartInfo(1).max_score }}点)</span
                                    >
                                    <span
                                        class="text-lg font-bold"
                                        :class="
                                            getPartInfo(1).average >= 0
                                                ? 'text-blue-600'
                                                : 'text-red-600'
                                        "
                                        >{{ getPartInfo(1).average }}点</span
                                    >
                                </div>
                                <div class="relative bg-gray-200 rounded-full h-4">
                                    <!-- ゼロライン -->
                                    <div
                                        class="absolute top-0 bottom-0 w-0.5 bg-gray-500 z-10"
                                        :style="`left: ${getZeroLinePosition(1)}%`"
                                    ></div>
                                    <div
                                        class="h-4 rounded-full transition-all"
                                        :class="
                                            getPartInfo(1).average >= 0
                                                ? 'bg-blue-500'
                                                : 'bg-red-400'
                                        "
                                        :style="`width: ${getPartBarWidth(1, getPartInfo(1).average)}%`"
                                    ></div>
                                </div>
                            </div>
                            <!-- Part 2 -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700"
                                        >Part 2 - 空間把握力 ({{ getPartInfo(2).min_score }}〜{{ getPartInfo(2).max_score }}点)</span
                                    >
                                    <span
                                        class="text-lg font-bold"
                                        :class="
                                            getPartInfo(2).average >= 0
                                                ? 'text-green-600'
                                                : 'text-red-600'
                                        "
                                        >{{ getPartInfo(2).average }}点</span
                                    >
                                </div>
                                <div class="relative bg-gray-200 rounded-full h-4">
                                    <!-- ゼロライン -->
                                    <div
                                        class="absolute top-0 bottom-0 w-0.5 bg-gray-500 z-10"
                                        :style="`left: ${getZeroLinePosition(2)}%`"
                                    ></div>
                                    <div
                                        class="h-4 rounded-full transition-all"
                                        :class="
                                            getPartInfo(2).average >= 0
                                                ? 'bg-green-500'
                                                : 'bg-red-400'
                                        "
                                        :style="`width: ${getPartBarWidth(2, getPartInfo(2).average)}%`"
                                    ></div>
                                </div>
                            </div>
                            <!-- Part 3 -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700"
                                        >Part 3 - 問題解決力 ({{ getPartInfo(3).min_score }}〜{{ getPartInfo(3).max_score }}点)</span
                                    >
                                    <span
                                        class="text-lg font-bold"
                                        :class="
                                            getPartInfo(3).average >= 0
                                                ? 'text-purple-600'
                                                : 'text-red-600'
                                        "
                                        >{{ getPartInfo(3).average }}点</span
                                    >
                                </div>
                                <div class="relative bg-gray-200 rounded-full h-4">
                                    <!-- ゼロライン -->
                                    <div
                                        class="absolute top-0 bottom-0 w-0.5 bg-gray-500 z-10"
                                        :style="`left: ${getZeroLinePosition(3)}%`"
                                    ></div>
                                    <div
                                        class="h-4 rounded-full transition-all"
                                        :class="
                                            getPartInfo(3).average >= 0
                                                ? 'bg-purple-500'
                                                : 'bg-red-400'
                                        "
                                        :style="`width: ${getPartBarWidth(3, getPartInfo(3).average)}%`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 月別受験者数 -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">月別受験者数(2025年)</h2>
                        <div class="space-y-3">
                            <div
                                v-for="month in [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]"
                                :key="month"
                                class="flex items-center"
                            >
                                <div class="w-16 text-sm font-medium text-gray-700">
                                    {{ month }}月
                                </div>
                                <div class="flex-1">
                                    <div class="bg-gray-200 rounded-full h-5">
                                        <div
                                            class="bg-indigo-500 h-5 rounded-full flex items-center justify-end pr-2 transition-all"
                                            :style="`width: ${
                                                (stats.monthly_data[month] / maxMonthlyCount) * 100
                                            }%`"
                                        >
                                            <span
                                                v-if="stats.monthly_data[month] > 0"
                                                class="text-xs text-white font-semibold"
                                            >
                                                {{ stats.monthly_data[month] }}人
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 補足情報 -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-start">
                        <svg
                            class="w-6 h-6 text-blue-600 mt-1 mr-3 flex-shrink-0"
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
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">
                                統計情報について
                            </h3>
                            <p class="text-sm text-blue-800 mb-2">
                                上記のグラフは実際のデータベースから集計された情報です。
                            </p>
                            <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
                                <li>
                                    得点は95点満点で計算されています(Part1: 40点、Part2:
                                    30点、Part3: 25点)
                                </li>
                                <li>正答: +1点、誤答: -0.25点、未回答: 0点</li>
                                <li>
                                    ランク基準:
                                    Platinum(61点以上)、Gold(51-60点)、Silver(36-50点)、Bronze(35点以下)
                                </li>
                                <li>完了したセッションのみが集計対象です</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
