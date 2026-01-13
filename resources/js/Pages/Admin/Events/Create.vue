<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed, watch, reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface QuestionItem {
    id: number;
    part: number;
    number: number;
    text: string;
    image: string | null;
}

const props = defineProps<{
    randomPassphrase?: string;
    allQuestions?: QuestionItem[];
}>();

// 各試験タイプのデフォルト設定
const examPresets = {
    full: {
        part1_questions: 40,
        part1_time: 10, // 分単位
        part2_questions: 30,
        part2_time: 15,
        part3_questions: 25,
        part3_time: 30,
    },
    "45min": {
        part1_questions: 30,
        part1_time: 7.5,
        part2_questions: 20,
        part2_time: 10,
        part3_questions: 15,
        part3_time: 18,
    },
    "30min": {
        part1_questions: 20,
        part1_time: 5,
        part2_questions: 13,
        part2_time: 6.5,
        part3_questions: 10,
        part3_time: 12,
    },
    custom: {
        part1_questions: 40,
        part1_time: 0,
        part2_questions: 30,
        part2_time: 0,
        part3_questions: 25,
        part3_time: 0,
    },
};

const form = reactive({
    name: "",
    passphrase: "",
    begin: "",
    end: "",
    exam_type: "full" as "30min" | "45min" | "full" | "custom",
    question_selection_mode: "sequential" as "sequential" | "random" | "custom",
    part1_questions: 40,
    part1_time: 10,
    part2_questions: 30,
    part2_time: 15,
    part3_questions: 25,
    part3_time: 30,
    custom_question_ids: [] as number[],
});

const errors = reactive<Record<string, string>>({});
const isGenerating = ref(false);
const processing = ref(false);

// モーダル表示状態
const showQuestionModal = ref(false);
// モーダル内での一時的な選択状態
const tempSelectedIds = ref<number[]>([]);

// カスタム形式が選択されているかどうか
const isCustom = computed(() => form.exam_type === "custom");

// カスタム問題選択モードかどうか（exam_typeがcustomかつquestion_selection_modeがcustom）
const isCustomQuestionMode = computed(() => form.exam_type === "custom" && form.question_selection_mode === "custom");

// パートごとの問題一覧
const questionsByPart = computed(() => {
    const questions = props.allQuestions || [];
    return {
        1: questions.filter(q => q.part === 1),
        2: questions.filter(q => q.part === 2),
        3: questions.filter(q => q.part === 3),
    };
});

// パートごとの選択数
const selectedCountByPart = computed(() => {
    const questions = props.allQuestions || [];
    const selectedSet = new Set(form.custom_question_ids);
    return {
        1: questions.filter(q => q.part === 1 && selectedSet.has(q.id)).length,
        2: questions.filter(q => q.part === 2 && selectedSet.has(q.id)).length,
        3: questions.filter(q => q.part === 3 && selectedSet.has(q.id)).length,
    };
});

// モーダル内での一時選択数
const tempSelectedCountByPart = computed(() => {
    const questions = props.allQuestions || [];
    const selectedSet = new Set(tempSelectedIds.value);
    return {
        1: questions.filter(q => q.part === 1 && selectedSet.has(q.id)).length,
        2: questions.filter(q => q.part === 2 && selectedSet.has(q.id)).length,
        3: questions.filter(q => q.part === 3 && selectedSet.has(q.id)).length,
    };
});

// 問題の選択/解除（モーダル内で使用）
const toggleQuestion = (questionId: number) => {
    const index = tempSelectedIds.value.indexOf(questionId);
    if (index === -1) {
        tempSelectedIds.value.push(questionId);
    } else {
        tempSelectedIds.value.splice(index, 1);
    }
};

// 問題が選択されているか（モーダル内で使用）
const isQuestionSelected = (questionId: number) => {
    return tempSelectedIds.value.includes(questionId);
};

// 画像パス生成関数
const getImagePath = (imageName: string | null): string => {
    if (!imageName || imageName.trim() === "") {
        return "";
    }
    const trimmedName = imageName.trim();
    const validExtensions = [".jpg", ".jpeg", ".png", ".gif", ".webp", ".svg"];
    const hasValidExtension = validExtensions.some(ext => trimmedName.toLowerCase().endsWith(ext));
    if (!hasValidExtension) {
        return "";
    }
    // public/images/questions/ から取得
    return `/images/questions/${trimmedName}`;
};

// パートの全問題を選択/解除（モーダル内で使用）
const toggleAllInPart = (part: number) => {
    const partQuestions = questionsByPart.value[part as 1 | 2 | 3];
    const partIds = partQuestions.map(q => q.id);
    const allSelected = partIds.every(id => tempSelectedIds.value.includes(id));
    
    if (allSelected) {
        // 全解除
        tempSelectedIds.value = tempSelectedIds.value.filter(id => !partIds.includes(id));
    } else {
        // 全選択
        const newIds = partIds.filter(id => !tempSelectedIds.value.includes(id));
        tempSelectedIds.value.push(...newIds);
    }
};

// ランダム出題ボタン押下
const selectRandomMode = () => {
    form.question_selection_mode = "random";
    form.custom_question_ids = [];
};

// 順番通り出題ボタン押下
const selectSequentialMode = () => {
    form.question_selection_mode = "sequential";
    form.custom_question_ids = [];
};

// 問題選択モーダルを開く
const openQuestionModal = () => {
    // 現在の選択状態をコピー
    tempSelectedIds.value = [...form.custom_question_ids];
    showQuestionModal.value = true;
};

// モーダルの保存ボタン押下
const saveQuestionSelection = () => {
    form.question_selection_mode = "custom";
    form.custom_question_ids = [...tempSelectedIds.value];
    
    // 選択した問題数に合わせてカスタムの出題数を更新
    const countByPart: { [key: number]: number } = { 1: 0, 2: 0, 3: 0 };
    tempSelectedIds.value.forEach(id => {
        const question = props.allQuestions?.find(q => q.id === id);
        if (question) {
            countByPart[question.part]++;
        }
    });
    form.part1_questions = countByPart[1] || 1;
    form.part2_questions = countByPart[2] || 1;
    form.part3_questions = countByPart[3] || 1;
    
    showQuestionModal.value = false;
};

// モーダルのキャンセル
const cancelQuestionModal = () => {
    showQuestionModal.value = false;
};

// 試験タイプが変更されたらリセット
watch(
    () => form.exam_type,
    newType => {
        const preset = examPresets[newType as keyof typeof examPresets];
        if (preset) {
            form.part1_questions = preset.part1_questions;
            form.part1_time = preset.part1_time;
            form.part2_questions = preset.part2_questions;
            form.part2_time = preset.part2_time;
            form.part3_questions = preset.part3_questions;
            form.part3_time = preset.part3_time;
        }
    }
);

const generatePassphrase = async () => {
    isGenerating.value = true;
    try {
        const response = await fetch(route("admin.events.generate-passphrase"));
        const data = await response.json();
        form.passphrase = data.passphrase;
    } catch (error) {
        console.error("パスフレーズ生成エラー:", error);
        alert("パスフレーズの生成に失敗しました。");
    } finally {
        isGenerating.value = false;
    }
};

const submit = () => {
    // エラーをクリア
    Object.keys(errors).forEach(key => delete errors[key]);

    // フォームデータをコピーして時間を変換
    const submitData: Record<string, any> = {
        name: form.name,
        passphrase: form.passphrase,
        begin: form.begin,
        end: form.end,
        exam_type: form.exam_type,
        part1_questions: form.part1_questions,
        part1_time: Math.round(form.part1_time * 60),
        part2_questions: form.part2_questions,
        part2_time: Math.round(form.part2_time * 60),
        part3_questions: form.part3_questions,
        part3_time: Math.round(form.part3_time * 60),
        question_selection_mode: form.question_selection_mode,
    };

    // カスタム問題選択モードの場合、選択した問題IDを追加
    if (form.question_selection_mode === 'custom') {
        submitData.custom_question_ids = form.custom_question_ids;
    }

    console.log("送信するデータ:", submitData);

    processing.value = true;

    // router.postを使って送信
    router.post(route("admin.events.store"), submitData, {
        onSuccess: () => {
            alert("イベントを作成しました。");
        },
        onError: (responseErrors: any) => {
            console.error("エラー:", responseErrors);
            Object.assign(errors, responseErrors);
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="イベント作成" />

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-6">
                    <Link
                        :href="route('admin.events.index')"
                        class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4"
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
                        イベント一覧に戻る
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900">新規イベント作成</h1>
                    <p class="mt-2 text-gray-600">セッションコードを発行するイベントを作成します</p>
                </div>

                <!-- フォーム -->
                <div class="bg-white rounded-lg shadow p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- イベント名 -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                イベント名
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="例: 2025年度 春期 適性検査"
                            />
                            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
                                {{ errors.name }}
                            </p>
                        </div>

                        <!-- パスフレーズ(セッションコード) -->
                        <div>
                            <label
                                for="passphrase"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                セッションコード
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-2">
                                <input
                                    id="passphrase"
                                    v-model="form.passphrase"
                                    type="text"
                                    required
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
                                    placeholder="例: abcd1234"
                                />
                                <button
                                    type="button"
                                    @click="generatePassphrase"
                                    :disabled="isGenerating"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                                >
                                    <span v-if="!isGenerating">ランダム生成</span>
                                    <span v-else>生成中...</span>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                受験者がアクセスする際に使用するコードです
                            </p>
                            <p v-if="errors.passphrase" class="mt-1 text-sm text-red-600">
                                {{ errors.passphrase }}
                            </p>
                        </div>

                        <!-- 開始日時 -->
                        <div>
                            <label for="begin" class="block text-sm font-medium text-gray-700 mb-2">
                                開始日時
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="begin"
                                v-model="form.begin"
                                type="datetime-local"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="errors.begin" class="mt-1 text-sm text-red-600">
                                {{ errors.begin }}
                            </p>
                        </div>

                        <!-- 終了日時 -->
                        <div>
                            <label for="end" class="block text-sm font-medium text-gray-700 mb-2">
                                終了日時
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="end"
                                v-model="form.end"
                                type="datetime-local"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p v-if="errors.end" class="mt-1 text-sm text-red-600">
                                {{ errors.end }}
                            </p>
                        </div>

                        <!-- 出題形式 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                出題形式
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50': form.exam_type === 'full',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="full"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">フル版(推奨)</div>
                                        <div class="text-sm text-gray-500">
                                            Part1: 40問(10分)、Part2: 30問(15分)、Part3: 25問(30分)
                                        </div>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50': form.exam_type === '45min',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="45min"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">45分版</div>
                                        <div class="text-sm text-gray-500">
                                            Part1: 30問(7.5分)、Part2: 20問(10分)、Part3: 15問(18分)
                                        </div>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50': form.exam_type === '30min',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="30min"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">30分版</div>
                                        <div class="text-sm text-gray-500">
                                            Part1: 20問(5分)、Part2: 13問(6.5分)、Part3: 10問(12分)
                                        </div>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50': form.exam_type === 'custom',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="custom"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">カスタム</div>
                                        <div class="text-sm text-gray-500">
                                            問題数と制限時間を自由に設定
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <p v-if="errors.exam_type" class="mt-1 text-sm text-red-600">
                                {{ errors.exam_type }}
                            </p>
                        </div>

                        <!-- カスタム設定 -->
                        <div
                            v-if="isCustom"
                            class="bg-gray-50 p-6 rounded-lg border border-gray-200 space-y-6"
                        >
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">カスタム設定</h3>

                            <!-- 第一部 -->
                            <div class="space-y-3">
                                <h4 class="font-medium text-gray-900">第一部 (最大40問)</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            for="part1_questions"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            問題数
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="part1_questions"
                                            v-model.number="form.part1_questions"
                                            type="number"
                                            min="1"
                                            max="40"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <p
                                            v-if="errors.part1_questions"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.part1_questions }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="part1_time"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            制限時間(分)
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="part1_time"
                                            v-model.number="form.part1_time"
                                            type="number"
                                            min="0"
                                            step="0.5"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="0=無制限"
                                        />
                                        <p
                                            v-if="errors.part1_time"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.part1_time }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 第二部 -->
                            <div class="space-y-3">
                                <h4 class="font-medium text-gray-900">第二部 (最大30問)</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            for="part2_questions"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            問題数
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="part2_questions"
                                            v-model.number="form.part2_questions"
                                            type="number"
                                            min="1"
                                            max="30"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <p
                                            v-if="errors.part2_questions"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.part2_questions }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="part2_time"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            制限時間(分)
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="part2_time"
                                            v-model.number="form.part2_time"
                                            type="number"
                                            min="0"
                                            step="0.5"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="0=無制限"
                                        />
                                        <p
                                            v-if="errors.part2_time"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.part2_time }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 第三部 -->
                            <div class="space-y-3">
                                <h4 class="font-medium text-gray-900">第三部 (最大25問)</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            for="part3_questions"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            問題数
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="part3_questions"
                                            v-model.number="form.part3_questions"
                                            type="number"
                                            min="1"
                                            max="25"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <p
                                            v-if="errors.part3_questions"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.part3_questions }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="part3_time"
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            制限時間(分)
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="part3_time"
                                            v-model.number="form.part3_time"
                                            type="number"
                                            min="0"
                                            step="0.5"
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="0=無制限"
                                        />
                                        <p
                                            v-if="errors.part3_time"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ errors.part3_time }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                <p class="text-sm text-blue-800">
                                    💡 制限時間に0を設定すると無制限になります
                                </p>
                            </div>

                            <!-- カスタム時のみ: 出題方法選択 -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    出題方法
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        @click="selectSequentialMode"
                                        class="px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center space-x-2"
                                        :class="form.question_selection_mode === 'sequential' 
                                            ? 'border-purple-500 bg-purple-50 text-purple-700' 
                                            : 'border-gray-300 bg-white text-gray-700 hover:border-purple-300 hover:bg-purple-50'"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                        </svg>
                                        <span class="font-medium">順番通りに出題する</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="selectRandomMode"
                                        class="px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center space-x-2"
                                        :class="form.question_selection_mode === 'random' 
                                            ? 'border-green-500 bg-green-50 text-green-700' 
                                            : 'border-gray-300 bg-white text-gray-700 hover:border-green-300 hover:bg-green-50'"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span class="font-medium">ランダムに出題する</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="openQuestionModal"
                                        class="px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center space-x-2"
                                        :class="form.question_selection_mode === 'custom' 
                                            ? 'border-blue-500 bg-blue-50 text-blue-700' 
                                            : 'border-gray-300 bg-white text-gray-700 hover:border-blue-300 hover:bg-blue-50'"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        <span class="font-medium">出題する問題を選択する</span>
                                    </button>
                                </div>
                                
                                <!-- 現在の選択状態を表示 -->
                                <div v-if="form.question_selection_mode === 'sequential'" class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                    <p class="text-sm text-purple-700">
                                        ✓ 問題番号順に出題されます
                                    </p>
                                </div>
                                <div v-else-if="form.question_selection_mode === 'random'" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p class="text-sm text-green-700">
                                        ✓ 各パートの設定問題数分、ランダムに出題されます（同じ問題は出題されません）
                                    </p>
                                </div>
                                <div v-else-if="form.question_selection_mode === 'custom'" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-blue-700">
                                                選択済み: {{ form.custom_question_ids.length }}問
                                            </p>
                                            <p class="text-xs text-blue-600 mt-1">
                                                第一部: {{ selectedCountByPart[1] }}問 / 
                                                第二部: {{ selectedCountByPart[2] }}問 / 
                                                第三部: {{ selectedCountByPart[3] }}問
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="openQuestionModal"
                                            class="text-sm text-blue-600 hover:text-blue-800 underline"
                                        >
                                            変更する
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 通常時（非カスタム）: 出題方法選択 -->
                        <div v-if="!isCustom">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                出題方法
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    @click="selectSequentialMode"
                                    class="px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center space-x-2"
                                    :class="form.question_selection_mode === 'sequential' 
                                        ? 'border-purple-500 bg-purple-50 text-purple-700' 
                                        : 'border-gray-300 bg-white text-gray-700 hover:border-purple-300 hover:bg-purple-50'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    <span class="font-medium">順番通りに出題する</span>
                                </button>
                                <button
                                    type="button"
                                    @click="selectRandomMode"
                                    class="px-4 py-3 rounded-lg border-2 transition-all duration-200 flex items-center space-x-2"
                                    :class="form.question_selection_mode === 'random' 
                                        ? 'border-green-500 bg-green-50 text-green-700' 
                                        : 'border-gray-300 bg-white text-gray-700 hover:border-green-300 hover:bg-green-50'"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span class="font-medium">ランダムに出題する</span>
                                </button>
                            </div>
                            
                            <!-- 現在の選択状態を表示 -->
                            <div v-if="form.question_selection_mode === 'sequential'" class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                                <p class="text-sm text-purple-700">
                                    ✓ 問題番号順に出題されます
                                </p>
                            </div>
                            <div v-else-if="form.question_selection_mode === 'random'" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-sm text-green-700">
                                    ✓ ランダムに出題されます（同じ問題は出題されません）
                                </p>
                            </div>
                        </div>

                        <!-- ボタン -->
                        <div class="flex justify-end space-x-3 pt-4">
                            <Link
                                :href="route('admin.events.index')"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                キャンセル
                            </Link>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="!processing">作成する</span>
                                <span v-else>作成中...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 問題選択モーダル -->
        <Teleport to="body">
            <div
                v-if="showQuestionModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click.self="cancelQuestionModal"
            >
                <!-- オーバーレイ -->
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

                <!-- モーダル本体 -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                        <!-- ヘッダー -->
                        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">出題する問題を選択</h2>
                                <p class="text-sm text-gray-500 mt-1">
                                    問題をクリックして選択してください（選択中: {{ tempSelectedIds.length }}問）
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="cancelQuestionModal"
                                class="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- コンテンツ -->
                        <div class="overflow-y-auto px-6 py-4" style="max-height: calc(90vh - 140px);">
                            <div v-if="!props.allQuestions || props.allQuestions.length === 0" class="text-center py-8">
                                <p class="text-gray-500">問題データが読み込まれていません</p>
                            </div>

                            <div v-else class="space-y-6">
                                <!-- 第一部 -->
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-900">
                                            第一部 
                                            <span class="text-blue-600 ml-2">
                                                ({{ tempSelectedCountByPart[1] }}/{{ questionsByPart[1].length }}問選択中)
                                            </span>
                                        </h3>
                                        <button
                                            type="button"
                                            @click="toggleAllInPart(1)"
                                            class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                                        >
                                            全選択/解除
                                        </button>
                                    </div>
                                    <div class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
                                        <div
                                            v-for="question in questionsByPart[1]"
                                            :key="question.id"
                                            @click="toggleQuestion(question.id)"
                                            class="flex items-center p-3 cursor-pointer transition-colors"
                                            :class="isQuestionSelected(question.id) ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                        >
                                            <div class="flex-shrink-0 mr-3">
                                                <div
                                                    class="w-5 h-5 border-2 rounded flex items-center justify-center transition-colors"
                                                    :class="isQuestionSelected(question.id) ? 'bg-blue-500 border-blue-500' : 'border-gray-300'"
                                                >
                                                    <svg v-if="isQuestionSelected(question.id)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900">問{{ question.number }}</p>
                                                <p class="text-sm text-gray-600 truncate">{{ question.text }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 第二部 -->
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-900">
                                            第二部 
                                            <span class="text-blue-600 ml-2">
                                                ({{ tempSelectedCountByPart[2] }}/{{ questionsByPart[2].length }}問選択中)
                                            </span>
                                        </h3>
                                        <button
                                            type="button"
                                            @click="toggleAllInPart(2)"
                                            class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                                        >
                                            全選択/解除
                                        </button>
                                    </div>
                                    <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                                        <div
                                            v-for="question in questionsByPart[2]"
                                            :key="question.id"
                                            @click="toggleQuestion(question.id)"
                                            class="flex items-center p-3 cursor-pointer transition-colors"
                                            :class="isQuestionSelected(question.id) ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                        >
                                            <div class="flex-shrink-0 mr-3">
                                                <div
                                                    class="w-5 h-5 border-2 rounded flex items-center justify-center transition-colors"
                                                    :class="isQuestionSelected(question.id) ? 'bg-blue-500 border-blue-500' : 'border-gray-300'"
                                                >
                                                    <svg v-if="isQuestionSelected(question.id)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0 mr-3">
                                                <p class="text-sm font-medium text-gray-900">問{{ question.number }}</p>
                                                <p class="text-sm text-gray-600 truncate">{{ question.text }}</p>
                                            </div>
                                            <!-- 画像を横に表示 -->
                                            <div v-if="question.image" class="flex-shrink-0">
                                                <img
                                                    :src="getImagePath(question.image)"
                                                    :alt="`問${question.number}`"
                                                    class="h-20 w-auto border rounded bg-white"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 第三部 -->
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-900">
                                            第三部 
                                            <span class="text-blue-600 ml-2">
                                                ({{ tempSelectedCountByPart[3] }}/{{ questionsByPart[3].length }}問選択中)
                                            </span>
                                        </h3>
                                        <button
                                            type="button"
                                            @click="toggleAllInPart(3)"
                                            class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors"
                                        >
                                            全選択/解除
                                        </button>
                                    </div>
                                    <div class="divide-y divide-gray-100 max-h-60 overflow-y-auto">
                                        <div
                                            v-for="question in questionsByPart[3]"
                                            :key="question.id"
                                            @click="toggleQuestion(question.id)"
                                            class="flex items-center p-3 cursor-pointer transition-colors"
                                            :class="isQuestionSelected(question.id) ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                        >
                                            <div class="flex-shrink-0 mr-3">
                                                <div
                                                    class="w-5 h-5 border-2 rounded flex items-center justify-center transition-colors"
                                                    :class="isQuestionSelected(question.id) ? 'bg-blue-500 border-blue-500' : 'border-gray-300'"
                                                >
                                                    <svg v-if="isQuestionSelected(question.id)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900">問{{ question.number }}</p>
                                                <p class="text-sm text-gray-600 truncate">{{ question.text }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- フッター -->
                        <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                合計 <span class="font-bold text-blue-600">{{ tempSelectedIds.length }}</span> 問選択中
                            </p>
                            <div class="flex space-x-3">
                                <button
                                    type="button"
                                    @click="cancelQuestionModal"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    キャンセル
                                </button>
                                <button
                                    type="button"
                                    @click="saveQuestionSelection"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    保存する
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
