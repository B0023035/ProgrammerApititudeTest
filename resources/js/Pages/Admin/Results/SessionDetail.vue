<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Answer {
    id: number;
    question_id: number;
    selected_answer: string | null;
    is_correct: boolean;
    question: {
        id: number;
        part: number;
        question_text: string;
        correct_answer: string;
    };
}

interface Session {
    id: number;
    session_uuid: string;
    user_id: number;
    total_score: number;
    rank: string;
    started_at: string;
    finished_at: string;
    part1_score: number;
    part2_score: number;
    part3_score: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    answers: Answer[];
}

interface Props {
    session: Session;
}

const props = defineProps<Props>();

const getRankColor = (rank: string) => {
    const colors: { [key: string]: string } = {
        Platinum: "text-purple-600 bg-purple-100",
        Gold: "text-yellow-600 bg-yellow-100",
        Silver: "text-gray-600 bg-gray-100",
        Bronze: "text-orange-600 bg-orange-100",
    };
    return colors[rank] || "text-gray-600 bg-gray-100";
};

const getPartAnswers = (part: number) => {
    return props.session.answers.filter((a) => a.question.part === part);
};

const getPartAccuracy = (part: number) => {
    const answers = getPartAnswers(part);
    if (answers.length === 0) return 0;
    const correct = answers.filter((a) => a.is_correct).length;
    return Math.round((correct / answers.length) * 100);
};
</script>

<template>
    <AdminLayout>
        <Head :title="`セッション詳細 - ${session.user.name}`" />

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

                <!-- セッション情報カード -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                受験セッション詳細
                            </h1>
                            <Link
                                :href="
                                    route('admin.results.user-detail', {
                                        userId: session.user_id,
                                    })
                                "
                                class="text-blue-600 hover:text-blue-800 text-lg"
                            >
                                {{ session.user.name }}
                            </Link>
                            <p class="text-gray-600 text-sm mt-1">
                                {{ session.user.email }}
                            </p>
                        </div>
                        <span
                            :class="getRankColor(session.rank)"
                            class="px-4 py-2 rounded-lg text-lg font-bold"
                        >
                            {{ session.rank }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">開始日時</p>
                            <p class="text-sm font-medium">
                                {{
                                    new Date(session.started_at).toLocaleString(
                                        "ja-JP"
                                    )
                                }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">完了日時</p>
                            <p class="text-sm font-medium">
                                {{
                                    new Date(
                                        session.finished_at
                                    ).toLocaleString("ja-JP")
                                }}
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">
                                セッションID
                            </p>
                            <p class="text-xs font-mono">
                                {{ session.session_uuid.substring(0, 8) }}...
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">回答数</p>
                            <p class="text-sm font-medium">
                                {{ session.answers.length }}問
                            </p>
                        </div>
                    </div>
                </div>

                <!-- スコアカード -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div
                        class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">総合得点</div>
                        <div class="text-4xl font-bold">
                            {{ session.total_score }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">点</div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">Part 1</div>
                        <div class="text-4xl font-bold">
                            {{ session.part1_score }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">
                            正答率: {{ getPartAccuracy(1) }}%
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">Part 2</div>
                        <div class="text-4xl font-bold">
                            {{ session.part2_score }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">
                            正答率: {{ getPartAccuracy(2) }}%
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="text-sm opacity-90 mb-2">Part 3</div>
                        <div class="text-4xl font-bold">
                            {{ session.part3_score }}
                        </div>
                        <div class="text-sm opacity-75 mt-2">
                            正答率: {{ getPartAccuracy(3) }}%
                        </div>
                    </div>
                </div>

                <!-- 回答詳細 -->
                <div
                    v-for="part in [1, 2, 3]"
                    :key="part"
                    class="bg-white rounded-lg shadow-lg overflow-hidden mb-6"
                >
                    <div class="p-6 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-2xl font-bold text-gray-900">
                            Part {{ part }} - 回答詳細
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            {{
                                getPartAnswers(part).filter((a) => a.is_correct)
                                    .length
                            }}
                            / {{ getPartAnswers(part).length }} 問正解
                        </p>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="(answer, index) in getPartAnswers(part)"
                                :key="answer.id"
                                class="border rounded-lg p-4"
                                :class="
                                    answer.is_correct
                                        ? 'bg-green-50 border-green-200'
                                        : 'bg-red-50 border-red-200'
                                "
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p
                                            class="font-medium text-gray-900 mb-2"
                                        >
                                            問題 {{ index + 1 }}
                                        </p>
                                        <p class="text-sm text-gray-700 mb-3">
                                            {{ answer.question.question_text }}
                                        </p>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p
                                                    class="text-xs text-gray-600 mb-1"
                                                >
                                                    受験者の回答
                                                </p>
                                                <p
                                                    class="text-sm font-medium"
                                                    :class="
                                                        answer.is_correct
                                                            ? 'text-green-700'
                                                            : 'text-red-700'
                                                    "
                                                >
                                                    {{
                                                        answer.selected_answer ||
                                                        "未回答"
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-xs text-gray-600 mb-1"
                                                >
                                                    正解
                                                </p>
                                                <p
                                                    class="text-sm font-medium text-green-700"
                                                >
                                                    {{
                                                        answer.question
                                                            .correct_answer
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <span
                                            v-if="answer.is_correct"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800"
                                        >
                                            ✓ 正解
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800"
                                        >
                                            ✗ 不正解
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
