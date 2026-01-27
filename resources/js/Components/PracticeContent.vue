<template>
    <div class="p-4">
        <!-- ゲストモード表示 -->
        <div v-if="isGuest" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
            <div class="flex items-center gap-2">
                <span class="text-yellow-600 font-semibold">ゲストモード</span>
                <span class="text-yellow-700">解答は保存されません</span>
            </div>
        </div>

        <!-- タイマー & 練習終了ボタン -->
        <div class="flex justify-between items-center mb-4">
            <div class="text-xl font-bold">残り時間: {{ timerDisplay }}</div>
            <button
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                @click="$emit('update:showConfirm', true)"
                :disabled="form.processing"
            >
                {{ form.processing ? "送信中..." : "練習終了" }}
            </button>
        </div>

        <!-- 確認モーダル（練習終了） -->
        <div
            v-if="showConfirm"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white p-6 rounded shadow-lg w-96">
                <h3 class="text-lg font-semibold mb-4">確認</h3>
                <p class="mb-4">練習を終了しますか？</p>
                <div class="mb-4 text-sm text-gray-600">
                    回答済み: {{ getAnsweredCount() }} / {{ totalQuestions }}
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        @click="$emit('confirmFinish')"
                        :disabled="form?.processing || false"
                    >
                        {{ form?.processing ? "送信中..." : "OK" }}
                    </button>

                    <button
                        class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
                        @click="$emit('update:showConfirm', false)"
                        :disabled="form?.processing || false"
                    >
                        キャンセル
                    </button>
                </div>
                <div v-if="form.errors?.answers" class="mt-2 text-red-600 text-sm">
                    {{ form.errors.answers }}
                </div>
            </div>
        </div>

        <!-- 部ごとの説明文 -->
        <div class="mb-2 text-left font-semibold">
            <template v-if="currentQuestion.part === 1">
                いくつかの文字が一定の規則に従って並んでいます。あなたはその規則を見つけ出し、配列を完成させてください。
            </template>
            <template v-else-if="currentQuestion.part === 2">
                各列の左側にある四つの図は一定の順序で並んでいます。次にくるべき図はどれでしょうか。右側の五つの図の中から選んで下さい。
            </template>
            <template v-else>
                問題の下側に解答が5つありますが、正解は一つだけです。問題を解いてみて正しいと思う答えを選んでください。
            </template>
        </div>

        <!-- 問題番号 -->
        <div class="mb-2 text-left font-semibold">問題 {{ currentQuestion.number }}</div>

        <!-- 中段：問題文と選択肢 -->
        <div
            v-if="currentQuestion.part === 1 || currentQuestion.part === 2"
            class="flex gap-4 mb-4"
        >
            <!-- 左：問題文 -->
            <div class="flex-1">
                <p class="font-bold">{{ currentQuestion.text }}</p>
            </div>

            <!-- 右：選択肢 -->
            <div class="flex-1 grid grid-cols-2 gap-2">
                <button
                    v-for="choice in currentQuestion.choices"
                    :key="choice.id"
                    class="px-2 py-1 border rounded hover:bg-gray-100 transition-colors"
                    :class="{
                        'bg-green-300 border-green-500':
                            answerStatus[currentIndex].selected === choice.label,
                    }"
                    @click="$emit('handleAnswer', choice.label)"
                >
                    <div>{{ choice.label }}: {{ choice.text }}</div>
                    <img
                        v-if="choice.image"
                        :src="choice.image"
                        class="mt-1 max-w-full h-auto"
                        :alt="`選択肢${choice.label}`"
                    />
                </button>
            </div>
        </div>

        <!-- 第三部：縦並び -->
        <div v-else class="flex flex-col gap-4 mb-4">
            <p class="font-bold mb-2">{{ currentQuestion.text }}</p>
            <div class="grid grid-cols-1 gap-2">
                <button
                    v-for="choice in currentQuestion.choices"
                    :key="choice.id"
                    class="px-2 py-1 border rounded hover:bg-gray-100 transition-colors"
                    :class="{
                        'bg-green-300 border-green-500':
                            answerStatus[currentIndex].selected === choice.label,
                    }"
                    @click="$emit('handleAnswer', choice.label)"
                >
                    <div>{{ choice.label }}: {{ choice.text }}</div>
                    <img
                        v-if="choice.image"
                        :src="choice.image"
                        class="mt-1 max-w-full h-auto"
                        :alt="`選択肢${choice.label}`"
                    />
                </button>
            </div>
        </div>

        <!-- 下段：解答状況表とナビゲーション -->
        <div
            class="flex items-start justify-between mt-4"
            v-if="currentQuestion.part === 1 || currentQuestion.part === 2"
        >
            <!-- 左：解答状況表 -->
            <div class="overflow-x-auto flex-1">
                <table class="table-auto border-collapse border border-gray-300 text-center">
                    <thead>
                        <tr>
                            <th
                                v-for="(ans, idx) in answerStatus"
                                :key="'check-' + idx"
                                class="border p-1"
                            >
                                <input
                                    type="checkbox"
                                    v-model="ans.checked"
                                    class="form-checkbox"
                                />
                            </th>
                        </tr>
                        <tr>
                            <th
                                v-for="(ans, idx) in answerStatus"
                                :key="'num-' + idx"
                                class="border p-1 cursor-pointer hover:bg-gray-100 transition-colors"
                                @dblclick="$emit('jumpToQuestion', idx)"
                            >
                                {{ ans.questionNumber }}
                            </th>
                        </tr>
                        <tr>
                            <th
                                v-for="(ans, idx) in answerStatus"
                                :key="'sel-' + idx"
                                class="border p-1"
                            >
                                {{ ans.selected || "-" }}
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>

            <!-- 右：前後ボタン -->
            <div class="flex gap-2">
                <button
                    class="px-4 py-2 rounded transition-colors"
                    :class="currentIndex <= 0 
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
                        : 'bg-gray-400 text-white hover:bg-gray-500'"
                    :disabled="currentIndex <= 0"
                    @click="$emit('prevQuestion')"
                >
                    前の問題
                </button>
                <button
                    class="px-4 py-2 rounded transition-colors"
                    :class="currentIndex >= totalQuestions - 1 
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
                        : 'bg-gray-400 text-white hover:bg-gray-500'"
                    :disabled="currentIndex >= totalQuestions - 1"
                    @click="$emit('nextQuestion')"
                >
                    次の問題
                </button>
            </div>
        </div>

        <!-- 第三部：下段は縦並び -->
        <div v-else class="mt-4 flex flex-col gap-2">
            <div class="flex gap-2">
                <button
                    class="px-4 py-2 rounded transition-colors"
                    :class="currentIndex === 0 
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
                        : 'bg-gray-400 text-white hover:bg-gray-500'"
                    :disabled="currentIndex === 0"
                    @click="$emit('prevQuestion')"
                >
                    前の問題
                </button>
                <button
                    class="px-4 py-2 rounded transition-colors"
                    :class="currentIndex === totalQuestions - 1 
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed' 
                        : 'bg-gray-400 text-white hover:bg-gray-500'"
                    :disabled="currentIndex === totalQuestions - 1"
                    @click="$emit('nextQuestion')"
                >
                    次の問題
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="table-auto border-collapse border border-gray-300 text-center">
                    <thead>
                        <tr>
                            <th
                                v-for="(ans, idx) in answerStatus"
                                :key="'check-' + idx"
                                class="border p-1"
                            >
                                <input
                                    type="checkbox"
                                    v-model="ans.checked"
                                    class="form-checkbox"
                                />
                            </th>
                        </tr>
                        <tr>
                            <th
                                v-for="(ans, idx) in answerStatus"
                                :key="'num-' + idx"
                                class="border p-1 cursor-pointer hover:bg-gray-100 transition-colors"
                                @dblclick="$emit('jumpToQuestion', idx)"
                            >
                                {{ ans.questionNumber }}
                            </th>
                        </tr>
                        <tr>
                            <th
                                v-for="(ans, idx) in answerStatus"
                                :key="'sel-' + idx"
                                class="border p-1"
                            >
                                {{ ans.selected || "-" }}
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    currentQuestion: any;
    answerStatus: any[];
    currentIndex: number;
    form: {
        processing: boolean;
        errors?: Record<string, any>;
        [key: string]: any;
    };
    showConfirm: boolean;
    isGuest: boolean;
    totalQuestions: number;
    timerDisplay: string;
    getAnsweredCount: () => number;
}>();

defineEmits([
    "handleAnswer",
    "prevQuestion",
    "nextQuestion",
    "jumpToQuestion",
    "confirmFinish",
    "update:showConfirm",
    "getAnsweredCount",
]);
</script>

<style scoped>
.transition-colors {
    transition:
        background-color 0.2s ease-in-out,
        border-color 0.2s ease-in-out;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-checkbox {
    appearance: none;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    width: 1rem;
    height: 1rem;
    cursor: pointer;
}

.form-checkbox:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}
</style>
