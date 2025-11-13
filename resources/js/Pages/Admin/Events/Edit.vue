<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed, watch, reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Event {
    id: number;
    name: string;
    passphrase: string;
    begin: string;
    end: string;
    exam_type: string;
    part1_questions: number | null;
    part1_time: number | null;
    part2_questions: number | null;
    part2_time: number | null;
    part3_questions: number | null;
    part3_time: number | null;
}

const props = defineProps<{
    event: Event;
}>();

// 各試験タイプのデフォルト設定(秒単位)
const examPresets = {
    full: {
        part1_questions: 40,
        part1_time: 600, // 10分 = 600秒
        part2_questions: 30,
        part2_time: 900, // 15分 = 900秒
        part3_questions: 25,
        part3_time: 1800, // 30分 = 1800秒
    },
    "45min": {
        part1_questions: 30,
        part1_time: 450, // 7.5分 = 450秒
        part2_questions: 20,
        part2_time: 600, // 10分 = 600秒
        part3_questions: 15,
        part3_time: 1080, // 18分 = 1080秒
    },
    "30min": {
        part1_questions: 20,
        part1_time: 300, // 5分 = 300秒
        part2_questions: 13,
        part2_time: 390, // 6.5分 = 390秒
        part3_questions: 10,
        part3_time: 720, // 12分 = 720秒
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

// プリセット値がある場合はそれを使用、なければプリセットから取得
const getInitialValues = () => {
    const preset = examPresets[props.event.exam_type as keyof typeof examPresets];

    return {
        part1_questions: props.event.part1_questions ?? preset.part1_questions,
        part1_time: props.event.part1_time ?? preset.part1_time,
        part2_questions: props.event.part2_questions ?? preset.part2_questions,
        part2_time: props.event.part2_time ?? preset.part2_time,
        part3_questions: props.event.part3_questions ?? preset.part3_questions,
        part3_time: props.event.part3_time ?? preset.part3_time,
    };
};

const initialValues = getInitialValues();

const form = reactive({
    name: props.event.name,
    passphrase: props.event.passphrase,
    begin: props.event.begin,
    end: props.event.end,
    exam_type: props.event.exam_type as "30min" | "45min" | "full" | "custom",
    part1_questions: initialValues.part1_questions,
    part1_time: initialValues.part1_time / 60, // 秒→分に変換して表示
    part2_questions: initialValues.part2_questions,
    part2_time: initialValues.part2_time / 60, // 秒→分に変換して表示
    part3_questions: initialValues.part3_questions,
    part3_time: initialValues.part3_time / 60, // 秒→分に変換して表示
});

const errors = reactive<Record<string, string>>({});
const isGenerating = ref(false);
const processing = ref(false);

// カスタム形式が選択されているかどうか
const isCustom = computed(() => form.exam_type === "custom");

// 試験タイプが変更されたら自動で値を設定
watch(
    () => form.exam_type,
    newType => {
        const preset = examPresets[newType as keyof typeof examPresets];
        if (preset) {
            form.part1_questions = preset.part1_questions;
            form.part1_time = preset.part1_time / 60; // 秒→分
            form.part2_questions = preset.part2_questions;
            form.part2_time = preset.part2_time / 60; // 秒→分
            form.part3_questions = preset.part3_questions;
            form.part3_time = preset.part3_time / 60; // 秒→分
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
    const submitData = {
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
    };

    console.log("送信するデータ:", submitData);

    processing.value = true;

    // router.putを使って送信
    router.put(route("admin.events.update", { event: props.event.id }), submitData, {
        onSuccess: () => {
            alert("イベントを更新しました。");
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
        <Head title="イベント編集" />

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
                    <h1 class="text-3xl font-bold text-gray-900">イベント編集</h1>
                    <p class="mt-2 text-gray-600">イベント情報を編集します</p>
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
                                <span v-if="!processing">更新する</span>
                                <span v-else>更新中...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
