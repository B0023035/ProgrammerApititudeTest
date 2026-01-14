<script setup lang="ts">
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

interface PartScore {
    correct: number;
    total: number;
    percentage: number;
}

interface PartAnswers {
    score: PartScore;
}

interface Session {
    id: number;
    session_uuid: string;
    started_at: string;
    finished_at: string;
    total_score: number;
    total_questions: number;
    percentage: number;
    rank: string;
    event?: {
        id: number;
        name: string;
    } | null;
}

const props = defineProps<{
    session: Session;
    answersByPart: {
        [key: string]: PartAnswers;
    };
}>();

// 賞状モーダル
const showCertificate = ref(false);

const getRankColor = (rank: string) => {
    const colors: { [key: string]: string } = {
        Platinum: "text-purple-600 bg-purple-100",
        Gold: "text-yellow-600 bg-yellow-100",
        Silver: "text-gray-600 bg-gray-100",
        Bronze: "text-orange-600 bg-orange-100",
    };
    return colors[rank] || "text-gray-600 bg-gray-100";
};

const getPartColor = (part: number) => {
    const colors = [
        "from-blue-500 to-blue-600",
        "from-green-500 to-green-600",
        "from-orange-500 to-orange-600",
    ];
    return colors[part - 1] || "from-gray-500 to-gray-600";
};

// 賞状を表示
const openCertificate = () => {
    router.visit(route("exam.result", { sessionUuid: props.session.session_uuid }));
};
</script>

<template>
    <Head :title="`試験結果詳細`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                試験結果詳細
            </h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 戻るボタン -->
                <div class="mb-6">
                    <Link
                        :href="route('my-results')"
                        class="inline-flex items-center text-blue-600 hover:text-blue-800"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        一覧に戻る
                    </Link>
                </div>

                <!-- セッション情報カード -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">受験結果詳細</h1>
                            <p class="text-gray-600">
                                {{ session.event?.name || '一般試験' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span
                                :class="getRankColor(session.rank)"
                                class="px-4 py-2 rounded-lg text-lg font-bold"
                            >
                                {{ session.rank }}
                            </span>
                            <button
                                @click="openCertificate"
                                class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-medium"
                            >
                                賞状を表示
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">イベント</p>
                            <p class="text-sm font-medium">
                                {{ session.event?.name || '一般試験' }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">開始日時</p>
                            <p class="text-sm font-medium">
                                {{ new Date(session.started_at).toLocaleString("ja-JP") }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">完了日時</p>
                            <p class="text-sm font-medium">
                                {{ new Date(session.finished_at).toLocaleString("ja-JP") }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">問題数</p>
                            <p class="text-sm font-medium">{{ session.total_questions }}問</p>
                        </div>
                    </div>
                </div>

                <!-- スコアカード -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                        <div class="text-sm opacity-90 mb-2">総合得点</div>
                        <div class="text-4xl font-bold">{{ session.total_score }}</div>
                        <div class="text-sm opacity-75 mt-2">
                            {{ session.total_questions }}問中 ({{ session.percentage }}%)
                        </div>
                    </div>

                    <div
                        v-for="part in ['1', '2', '3']"
                        :key="part"
                        class="bg-gradient-to-br rounded-xl shadow-lg p-6 text-white"
                        :class="getPartColor(parseInt(part))"
                    >
                        <div class="text-sm opacity-90 mb-2">Part {{ part }}</div>
                        <div class="text-4xl font-bold">
                            {{ answersByPart[part]?.score?.correct || 0 }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">
                            {{ answersByPart[part]?.score?.total || 0 }}問中 
                            ({{ answersByPart[part]?.score?.percentage || 0 }}%)
                        </div>
                    </div>
                </div>

                <!-- パート別結果サマリー -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">パート別結果</h2>
                    <div class="space-y-4">
                        <div
                            v-for="part in ['1', '2', '3']"
                            :key="part"
                            class="border rounded-lg p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Part {{ part }}</h3>
                                    <p class="text-sm text-gray-600">
                                        正解: {{ answersByPart[part]?.score?.correct || 0 }}問 / 
                                        {{ answersByPart[part]?.score?.total || 0 }}問
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ answersByPart[part]?.score?.percentage || 0 }}%
                                    </div>
                                </div>
                            </div>
                            <!-- プログレスバー -->
                            <div class="mt-3 w-full bg-gray-200 rounded-full h-3">
                                <div
                                    class="h-3 rounded-full transition-all duration-500"
                                    :class="{
                                        'bg-green-500': (answersByPart[part]?.score?.percentage || 0) >= 70,
                                        'bg-yellow-500': (answersByPart[part]?.score?.percentage || 0) >= 50 && (answersByPart[part]?.score?.percentage || 0) < 70,
                                        'bg-red-500': (answersByPart[part]?.score?.percentage || 0) < 50,
                                    }"
                                    :style="{ width: (answersByPart[part]?.score?.percentage || 0) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="flex justify-center">
                    <Link
                        :href="route('my-results')"
                        class="inline-flex items-center px-6 py-3 bg-gray-600 rounded-md text-sm text-white hover:bg-gray-700"
                    >
                        ← 一覧に戻る
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
