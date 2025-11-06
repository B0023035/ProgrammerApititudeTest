<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Choice {
    label: string;
    text: string | null;
    image: string | null;
    is_correct: boolean;
}

interface QuestionDetail {
    question_id: number;
    question_number: number;
    question_text: string | null;
    question_image: string | null;
    user_choice: string | null;
    correct_choice: string;
    is_correct: boolean;
    choices: Choice[];
}

interface PartScore {
    correct: number;
    total: number;
    percentage: number;
}

interface PartAnswers {
    score: PartScore;
    questions: QuestionDetail[];
}

interface Session {
    id: number;
    session_uuid: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    started_at: string;
    finished_at: string;
    total_score: number;
    total_questions: number;
    percentage: number;
    rank: string;
}

interface Props {
    session: Session;
    answersByPart: {
        [key: string]: PartAnswers;
    };
}

const props = defineProps<Props>();

// 各パートの開閉状態を管理
const partExpanded = ref<{ [key: string]: boolean }>({
    "1": false,
    "2": false,
    "3": false,
});

// パートの開閉を切り替え
const togglePart = (part: string) => {
    partExpanded.value[part] = !partExpanded.value[part];
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

const getPartColor = (part: number) => {
    const colors = [
        "from-blue-500 to-blue-600",
        "from-green-500 to-green-600",
        "from-orange-500 to-orange-600",
    ];
    return colors[part - 1] || "from-gray-500 to-gray-600";
};

// 実際の回答数を計算（未回答を除く）
const getActualAnswerCount = () => {
    let count = 0;
    ["1", "2", "3"].forEach(part => {
        const questions = props.answersByPart[part]?.questions || [];
        count += questions.filter(q => q.user_choice !== null).length;
    });
    return count;
};

// 画像のパスを生成（Practice.vueと同じ方法）
const getImagePath = (imageName: string | null, imageType: "questions" | "choices"): string => {
    const rawImageName = imageName ? String(imageName) : null;

    if (
        !rawImageName ||
        rawImageName === "undefined" ||
        rawImageName === "null" ||
        rawImageName.trim() === ""
    ) {
        return "";
    }

    const trimmedName = rawImageName.trim();
    const validExtensions = [".jpg", ".jpeg", ".png", ".gif", ".webp", ".svg"];
    const hasValidExtension = validExtensions.some(ext => trimmedName.toLowerCase().endsWith(ext));

    if (!hasValidExtension) {
        return "";
    }

    try {
        // Viteの画像インポート方法を使用
        const modules = import.meta.glob("../images/**/*", {
            eager: true,
            as: "url",
        });
        const imagePath = `../images/${imageType}/${trimmedName}`;

        if (modules[imagePath]) {
            return modules[imagePath] as string;
        }

        // フォールバック: publicディレクトリから読み込む
        return `/images/${imageType}/${trimmedName}`;
    } catch (error) {
        console.error(`Image load error for ${imageName}:`, error);
        // エラー時はpublicディレクトリのパスを返す
        return `/images/${imageType}/${trimmedName}`;
    }
};

const getQuestionImagePath = (image: string | null) => {
    return getImagePath(image, "questions");
};

const getChoiceImagePath = (image: string | null) => {
    return getImagePath(image, "choices");
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
                                        userId: session.user.id,
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
                            <p class="text-sm text-gray-600 mb-1">セッションID</p>
                            <p class="text-xs font-mono">
                                {{ session.session_uuid.substring(0, 8) }}...
                            </p>
                        </div>
                        <div class="border rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">回答数</p>
                            <p class="text-sm font-medium">
                                {{ getActualAnswerCount() }}問 / {{ session.total_questions }}問
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
                        <div class="text-sm opacity-75 mt-2">
                            {{ getActualAnswerCount() }}問中 ({{ session.percentage }}%)
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
                            {{ answersByPart[part]?.score?.total || 0 }}問中 ({{
                                answersByPart[part]?.score?.percentage || 0
                            }}%)
                        </div>
                    </div>
                </div>

                <!-- 回答詳細 -->
                <div
                    v-for="part in ['1', '2', '3']"
                    :key="part"
                    class="bg-white rounded-lg shadow-lg overflow-hidden mb-6"
                >
                    <!-- パートヘッダー（クリック可能） -->
                    <div
                        @click="togglePart(part)"
                        class="p-6 border-b border-gray-200 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    Part {{ part }} - 回答詳細
                                </h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ answersByPart[part]?.score?.correct || 0 }} /
                                    {{ answersByPart[part]?.score?.total || 0 }} 問正解 ({{
                                        answersByPart[part]?.score?.percentage || 0
                                    }}%)
                                </p>
                            </div>
                            <svg
                                class="w-6 h-6 text-gray-600 transition-transform"
                                :class="{ 'rotate-180': partExpanded[part] }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- パート内容（アコーディオン） -->
                    <div v-show="partExpanded[part]" class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="(question, index) in answersByPart[part]?.questions || []"
                                :key="question.question_id"
                                class="border rounded-lg p-4"
                                :class="
                                    question.is_correct
                                        ? 'bg-green-50 border-green-200'
                                        : 'bg-red-50 border-red-200'
                                "
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <p class="font-medium text-gray-900">
                                                問題 {{ question.question_number }}
                                            </p>
                                            <span
                                                v-if="question.is_correct"
                                                class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                                            >
                                                ✓ 正解
                                            </span>
                                            <span
                                                v-else
                                                class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                                            >
                                                ✗ 不正解
                                            </span>
                                        </div>

                                        <!-- 問題文 -->
                                        <div class="mb-4">
                                            <p
                                                v-if="question.question_text"
                                                class="text-sm text-gray-700 mb-2"
                                            >
                                                {{ question.question_text }}
                                            </p>
                                            <div v-if="question.question_image" class="mt-2">
                                                <img
                                                    v-if="
                                                        getQuestionImagePath(
                                                            question.question_image
                                                        )
                                                    "
                                                    :src="
                                                        getQuestionImagePath(
                                                            question.question_image
                                                        )
                                                    "
                                                    :alt="`問題 ${question.question_number}`"
                                                    class="max-w-md rounded border"
                                                    @error="
                                                        e =>
                                                            ((
                                                                e.target as HTMLImageElement
                                                            ).style.display = 'none')
                                                    "
                                                />
                                                <div
                                                    v-else
                                                    class="flex items-center justify-center h-32 bg-gray-100 rounded border-2 border-dashed border-gray-300"
                                                >
                                                    <div class="text-center text-gray-500 text-xs">
                                                        <div>
                                                            画像: {{ question.question_image }}
                                                        </div>
                                                        <div class="mt-1">
                                                            ファイルが見つかりません
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 選択肢 -->
                                        <div class="space-y-2 mb-3">
                                            <div
                                                v-for="choice in question.choices"
                                                :key="choice.label"
                                                class="flex items-start p-2 rounded"
                                                :class="{
                                                    'bg-green-100 border border-green-300':
                                                        choice.is_correct,
                                                    'bg-red-100 border border-red-300':
                                                        choice.label === question.user_choice &&
                                                        !choice.is_correct,
                                                    'bg-gray-50':
                                                        choice.label !== question.user_choice &&
                                                        !choice.is_correct,
                                                }"
                                            >
                                                <span class="font-medium text-sm mr-2">
                                                    {{ choice.label }}.
                                                </span>
                                                <span v-if="choice.text" class="text-sm">
                                                    {{ choice.text }}
                                                </span>
                                                <img
                                                    v-if="choice.image"
                                                    :src="getChoiceImagePath(choice.image)"
                                                    :alt="`選択肢 ${choice.label}`"
                                                    class="max-w-xs"
                                                />
                                                <span
                                                    v-if="choice.is_correct"
                                                    class="ml-2 text-xs text-green-700"
                                                >
                                                    (正解)
                                                </span>
                                                <span
                                                    v-if="choice.label === question.user_choice"
                                                    class="ml-2 text-xs text-blue-700"
                                                >
                                                    (受験者の選択)
                                                </span>
                                            </div>
                                        </div>

                                        <!-- 回答情報 -->
                                        <div class="grid grid-cols-2 gap-4 pt-3 border-t">
                                            <div>
                                                <p class="text-xs text-gray-600 mb-1">
                                                    受験者の回答
                                                </p>
                                                <p
                                                    class="text-sm font-medium"
                                                    :class="
                                                        question.is_correct
                                                            ? 'text-green-700'
                                                            : 'text-red-700'
                                                    "
                                                >
                                                    {{ question.user_choice || "未回答" }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600 mb-1">正解</p>
                                                <p class="text-sm font-medium text-green-700">
                                                    {{ question.correct_choice }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                !answersByPart[part]?.questions ||
                                answersByPart[part].questions.length === 0
                            "
                            class="text-center text-gray-500 py-8"
                        >
                            このパートには回答がありません
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
