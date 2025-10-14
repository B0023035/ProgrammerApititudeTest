<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import * as Comlink from "comlink";

// Worker用の型定義
interface GradeCalculator {
    calculateGrade(score: number): Promise<string>;
    calculateAverage(grades: GradeData[]): Promise<number>;
    findHighest(grades: GradeData[]): Promise<number>;
    findLowest(grades: GradeData[]): Promise<number>;
    countByGrade(grades: GradeData[]): Promise<GradeCounts>;
    processGrades(grades: GradeData[]): Promise<ProcessedGrade[]>;
    calculateRankDistribution(grades: GradeData[]): Promise<RankDistribution>;
}

interface GradeData {
    id: number;
    name: string;
    subject: string;
    score: number;
}

interface ProcessedGrade extends GradeData {
    grade: string;
}

interface GradeCounts {
    A: number;
    B: number;
    C: number;
    D: number;
    F: number;
}

interface RankDistribution {
    [key: string]: number;
}

// Props定義（Laravelから渡されるデータ）
const props = defineProps<{
    sessions?: any[];
    users?: any[];
}>();

// 状態管理
const grades = ref<GradeData[]>([]);
const loading = ref(false);
const calculator = ref<Comlink.Remote<GradeCalculator> | null>(null);
const stats = ref({
    average: 0,
    highest: 0,
    lowest: 0,
    counts: { A: 0, B: 0, C: 0, D: 0, F: 0 },
});

// フィルター
const selectedSubject = ref("all");
const searchQuery = ref("");

// フィルタリングされた成績
const filteredGrades = computed(() => {
    let result = grades.value;

    if (selectedSubject.value !== "all") {
        result = result.filter((g) => g.subject === selectedSubject.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(
            (g) =>
                g.name.toLowerCase().includes(query) ||
                g.subject.toLowerCase().includes(query)
        );
    }

    return result;
});

// 科目リスト
const subjects = computed(() => {
    const uniqueSubjects = [
        ...new Set(grades.value.map((g) => g.subject)),
    ].sort();
    return ["all", ...uniqueSubjects];
});

// Web Workerの初期化
const initWorker = () => {
    const workerCode = `
        self.Comlink = {};
        importScripts('https://cdnjs.cloudflare.com/ajax/libs/comlink/4.4.1/comlink.min.js');

        class GradeCalculator {
            calculateGrade(score) {
                if (score >= 90) return 'A';
                if (score >= 80) return 'B';
                if (score >= 70) return 'C';
                if (score >= 60) return 'D';
                return 'F';
            }

            async calculateAverage(grades) {
                if (grades.length === 0) return 0;
                const sum = grades.reduce((acc, g) => acc + g.score, 0);
                return parseFloat((sum / grades.length).toFixed(2));
            }

            async findHighest(grades) {
                if (grades.length === 0) return 0;
                return Math.max(...grades.map(g => g.score));
            }

            async findLowest(grades) {
                if (grades.length === 0) return 0;
                return Math.min(...grades.map(g => g.score));
            }

            async countByGrade(grades) {
                const counts = { A: 0, B: 0, C: 0, D: 0, F: 0 };
                grades.forEach(g => {
                    const grade = this.calculateGrade(g.score);
                    counts[grade]++;
                });
                return counts;
            }

            async processGrades(grades) {
                await new Promise(resolve => setTimeout(resolve, 300));
                return grades.map(g => ({
                    ...g,
                    grade: this.calculateGrade(g.score)
                }));
            }

            async calculateRankDistribution(grades) {
                const distribution = {};
                grades.forEach(g => {
                    const rank = this.calculateGrade(g.score);
                    distribution[rank] = (distribution[rank] || 0) + 1;
                });
                return distribution;
            }
        }

        Comlink.expose(new GradeCalculator());
    `;

    const blob = new Blob([workerCode], { type: "application/javascript" });
    const worker = new Worker(URL.createObjectURL(blob));
    calculator.value = Comlink.wrap(worker) as Comlink.Remote<GradeCalculator>;
};

// サンプルデータの生成（実際はLaravelから取得）
const loadSampleData = () => {
    const subjects = ["規則発見力", "空間把握力", "問題解決力"];
    const students = [
        "山田太郎",
        "佐藤花子",
        "鈴木一郎",
        "田中美咲",
        "高橋健太",
        "渡辺あかり",
        "伊藤翔太",
        "中村さくら",
    ];

    const sampleGrades: GradeData[] = [];
    let id = 1;

    students.forEach((name) => {
        subjects.forEach((subject) => {
            sampleGrades.push({
                id: id++,
                name,
                subject,
                score: Math.floor(Math.random() * 41) + 60, // 60-100
            });
        });
    });

    grades.value = sampleGrades;
};

// 統計を計算
const calculateStats = async () => {
    if (!calculator.value || filteredGrades.value.length === 0) {
        return;
    }

    loading.value = true;

    try {
        const [average, highest, lowest, counts] = await Promise.all([
            calculator.value.calculateAverage(filteredGrades.value),
            calculator.value.findHighest(filteredGrades.value),
            calculator.value.findLowest(filteredGrades.value),
            calculator.value.countByGrade(filteredGrades.value),
        ]);

        stats.value = {
            average,
            highest,
            lowest,
            counts,
        };
    } catch (error) {
        console.error("統計計算エラー:", error);
    } finally {
        loading.value = false;
    }
};

// CSVエクスポート
const exportToCSV = async () => {
    if (!calculator.value) return;

    loading.value = true;
    const processedGrades = await calculator.value.processGrades(
        filteredGrades.value
    );
    loading.value = false;

    const headers = ["学生名", "科目", "点数", "評価"];
    const rows = processedGrades.map((g: ProcessedGrade) => [
        g.name,
        g.subject,
        g.score,
        g.grade,
    ]);

    const csvContent = [
        headers.join(","),
        ...rows.map((row) => row.join(",")),
    ].join("\n");

    const blob = new Blob(["\uFEFF" + csvContent], {
        type: "text/csv;charset=utf-8;",
    });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `成績データ_${new Date().toISOString().split("T")[0]}.csv`;
    link.click();
};

// ページ遷移
const goToUserDetail = (userId: number) => {
    router.visit(route("admin.results.user-detail", { userId }));
};

const goToStatistics = () => {
    router.visit(route("admin.results.statistics"));
};

// マウント時の処理
onMounted(() => {
    initWorker();
    loadSampleData();
    setTimeout(() => {
        calculateStats();
    }, 100);
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="成績管理システム" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
                >
                    <div class="p-6 border-b border-gray-200">
                        <div
                            class="flex items-center justify-between flex-wrap gap-4"
                        >
                            <h2
                                class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-blue-600"
                            >
                                📊 成績管理システム (Comlink版)
                            </h2>
                            <div class="flex gap-2">
                                <button
                                    @click="goToStatistics"
                                    class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors"
                                >
                                    📈 統計ページ
                                </button>
                                <button
                                    @click="exportToCSV"
                                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                                >
                                    📥 CSV出力
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- フィルター -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
                >
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    科目で絞り込み
                                </label>
                                <select
                                    v-model="selectedSubject"
                                    @change="calculateStats"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                    <option value="all">すべての科目</option>
                                    <option
                                        v-for="subject in subjects.filter(
                                            (s) => s !== 'all'
                                        )"
                                        :key="subject"
                                        :value="subject"
                                    >
                                        {{ subject }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    検索
                                </label>
                                <input
                                    v-model="searchQuery"
                                    @input="calculateStats"
                                    type="text"
                                    placeholder="学生名または科目で検索..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 統計カード -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6"
                >
                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">平均点</div>
                        <div class="text-4xl font-bold">
                            {{ loading ? "..." : stats.average }}
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">最高点</div>
                        <div class="text-4xl font-bold">
                            {{ loading ? "..." : stats.highest }}
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">最低点</div>
                        <div class="text-4xl font-bold">
                            {{ loading ? "..." : stats.lowest }}
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">データ数</div>
                        <div class="text-4xl font-bold">
                            {{ filteredGrades.length }}
                        </div>
                    </div>
                </div>

                <!-- 評価分布 -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6"
                >
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-4 text-gray-800">
                            評価分布
                        </h3>
                        <div
                            class="grid grid-cols-5 gap-4 text-center"
                            v-if="!loading"
                        >
                            <div
                                class="bg-green-100 rounded-lg p-4 border-2 border-green-500"
                            >
                                <div class="text-3xl font-bold text-green-600">
                                    A
                                </div>
                                <div class="text-2xl font-semibold mt-2">
                                    {{ stats.counts.A }}
                                </div>
                            </div>
                            <div
                                class="bg-blue-100 rounded-lg p-4 border-2 border-blue-500"
                            >
                                <div class="text-3xl font-bold text-blue-600">
                                    B
                                </div>
                                <div class="text-2xl font-semibold mt-2">
                                    {{ stats.counts.B }}
                                </div>
                            </div>
                            <div
                                class="bg-yellow-100 rounded-lg p-4 border-2 border-yellow-500"
                            >
                                <div class="text-3xl font-bold text-yellow-600">
                                    C
                                </div>
                                <div class="text-2xl font-semibold mt-2">
                                    {{ stats.counts.C }}
                                </div>
                            </div>
                            <div
                                class="bg-orange-100 rounded-lg p-4 border-2 border-orange-500"
                            >
                                <div class="text-3xl font-bold text-orange-600">
                                    D
                                </div>
                                <div class="text-2xl font-semibold mt-2">
                                    {{ stats.counts.D }}
                                </div>
                            </div>
                            <div
                                class="bg-red-100 rounded-lg p-4 border-2 border-red-500"
                            >
                                <div class="text-3xl font-bold text-red-600">
                                    F
                                </div>
                                <div class="text-2xl font-semibold mt-2">
                                    {{ stats.counts.F }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            計算中...
                        </div>
                    </div>
                </div>

                <!-- 成績一覧テーブル -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-4 text-gray-800">
                            成績一覧
                            <span class="text-sm font-normal text-gray-500"
                                >({{ filteredGrades.length }}件)</span
                            >
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            学生名
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            科目
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            点数
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            評価
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="grade in filteredGrades"
                                        :key="grade.id"
                                        class="hover:bg-gray-50 transition-colors cursor-pointer"
                                        @click="goToUserDetail(grade.id)"
                                    >
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                        >
                                            {{ grade.name }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        >
                                            {{ grade.subject }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                        >
                                            {{ grade.score }}点
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-bold"
                                        >
                                            <span
                                                :class="{
                                                    'text-green-600':
                                                        grade.score >= 90,
                                                    'text-blue-600':
                                                        grade.score >= 80 &&
                                                        grade.score < 90,
                                                    'text-yellow-600':
                                                        grade.score >= 70 &&
                                                        grade.score < 80,
                                                    'text-orange-600':
                                                        grade.score >= 60 &&
                                                        grade.score < 70,
                                                    'text-red-600':
                                                        grade.score < 60,
                                                }"
                                            >
                                                {{
                                                    grade.score >= 90
                                                        ? "A"
                                                        : grade.score >= 80
                                                        ? "B"
                                                        : grade.score >= 70
                                                        ? "C"
                                                        : grade.score >= 60
                                                        ? "D"
                                                        : "F"
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div
                                v-if="filteredGrades.length === 0"
                                class="text-center py-12 text-gray-500"
                            >
                                データがありません
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* カスタムスタイル */
</style>
