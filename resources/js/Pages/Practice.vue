<template>
    <div class="min-h-screen bg-gray-100">
        <Head :title="`練習問題 第${currentPart}部`" />

        <!-- 練習開始前ポップアップ -->
        <div
            v-if="showPracticeStartPopup"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        >
            <div class="bg-white rounded-lg p-8 shadow-xl max-w-lg w-full mx-4">
                <div class="text-center mb-6">
                    <div class="text-2xl font-bold text-gray-800 mb-2">
                        練習問題 第{{ currentPart }}部
                    </div>
                </div>

                <div class="space-y-4 mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg
                                class="w-5 h-5 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                            <span class="font-semibold text-blue-800">問題数</span>
                        </div>
                        <p class="text-blue-700">
                            全部で<span class="font-bold text-xl">{{ questions.length }}問</span
                            >出題されます
                        </p>
                    </div>

                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg
                                class="w-5 h-5 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <span class="font-semibold text-green-800">制限時間</span>
                        </div>
                        <p class="text-green-700">
                            <span class="font-bold text-xl">
                                {{ Math.floor((page.props.partTime || 300) / 60) }}分
                            </span>
                        </p>
                    </div>
                </div>

                <div class="text-center">
                    <button
                        @click="startPractice"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-8 rounded-lg transition-colors text-lg shadow-md"
                    >
                        練習を開始する
                    </button>
                </div>
            </div>
        </div>

        <!-- ナビゲーションバー -->
        <nav class="bg-white shadow" v-show="!showPracticeStartPopup">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold">練習問題システム</h1>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-600">{{
                            $page.props.auth?.user?.name || "ゲスト"
                        }}</span>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" v-show="!showPracticeStartPopup">
            <div class="p-4">
                <!-- 上部ヘッダー -->
                <div
                    class="flex justify-between items-center mb-4 bg-blue-50 border border-blue-200 rounded p-3"
                >
                    <div class="flex items-center gap-4">
                        <div class="text-xl font-bold text-blue-700">
                            練習問題 第{{ currentPart }}部
                        </div>
                        <div class="text-lg font-semibold text-blue-600">
                            残り時間: {{ timerDisplay }}
                        </div>
                    </div>
                    <button
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                        @click="showConfirm = true"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "送信中..." : getCompleteButtonText() }}
                    </button>
                </div>

                <!-- 確認モーダル -->
                <div
                    v-if="showConfirm"
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                >
                    <div class="bg-white p-6 rounded shadow-lg w-96">
                        <h3 class="text-lg font-semibold mb-4">確認</h3>
                        <p class="mb-4">{{ getConfirmMessage() }}</p>
                        <div class="mb-4 text-sm text-gray-600">
                            回答済み: {{ getAnsweredCount() }} / {{ questions.length }} 問
                        </div>
                        <div class="flex justify-end gap-2">
                            <button
                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                @click="confirmComplete"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? "送信中..." : "OK" }}
                            </button>
                            <button
                                class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
                                @click="showConfirm = false"
                                :disabled="form.processing"
                            >
                                キャンセル
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 問題表示エリア全体 -->
                <div class="problem-display-area">
                    <!-- 部ごとの説明文(全体の上部に表示) -->
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="font-semibold text-blue-800 text-base text-left">
                            <template v-if="currentPart === 1">
                                文字列のパターンを見つけて配列を完成させてください
                            </template>
                            <template v-else-if="currentPart === 2">
                                各列の左側にある四つの図は一定の順序で並んでいます。次にくるべき図はどれでしょうか。右側の五つの図の中から選んでください。
                            </template>
                            <template v-else>
                                問題の下側に解答が5つありますが、正解は一つだけです。問題を解いてみて正しいと思う答えを選んでください。
                            </template>
                        </div>
                    </div>

                    <!-- 第一部・第二部:4分割レイアウト -->
                    <div v-if="currentPart === 1 || currentPart === 2" class="mb-4">
                        <!-- 上段:問題エリアと選択肢 -->
                        <div class="flex gap-4 mb-4">
                            <!-- 左上:問題エリア -->
                            <div
                                class="w-1/2 bg-white p-4 rounded-lg border border-gray-200 shadow-sm min-h-[200px]"
                            >
                                <!-- 問題番号 -->
                                <div class="mb-3 text-left">
                                    <span class="text-xl font-bold text-gray-800"
                                        >問 {{ currentQuestion.number }}</span
                                    >
                                </div>

                                <!-- 問題文 -->
                                <div v-if="currentQuestion.text" class="mb-4">
                                    <p
                                        class="text-4xl leading-relaxed text-gray-800 font-medium tracking-widest"
                                    >
                                        {{ currentQuestion.text }}
                                    </p>
                                </div>

                                <!-- 第二部の問題画像表示 -->
                                <div
                                    v-if="shouldShowQuestionImage"
                                    class="flex items-center justify-center"
                                >
                                    <div class="w-full max-w-xs">
                                        <img
                                            v-if="getImagePath(currentQuestion.image, 'questions')"
                                            :src="getImagePath(currentQuestion.image, 'questions')"
                                            class="w-full h-auto rounded-lg shadow-md border border-gray-200"
                                            :alt="`問題${currentQuestion.number}`"
                                            @error="handleImageError"
                                            @load="handleImageLoad"
                                        />
                                        <div
                                            v-else
                                            class="flex items-center justify-center h-32 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300"
                                        >
                                            <div class="text-center text-gray-500">
                                                <svg
                                                    class="w-8 h-8 mx-auto mb-2"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2z"
                                                    ></path>
                                                </svg>
                                                <div class="text-sm">画像を読み込み中...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 右上:選択肢エリア -->
                            <div
                                class="w-1/2 bg-white p-4 rounded-lg border border-gray-200 shadow-sm"
                            >
                                <div class="mb-3">
                                    <h3
                                        class="text-base font-semibold text-gray-800 border-b border-gray-200 pb-2"
                                    >
                                        選択肢を選んでください
                                    </h3>
                                </div>

                                <!-- 選択肢ボタン(横並び) -->
                                <div class="flex flex-wrap gap-3">
                                    <button
                                        v-for="(choice, choiceIndex) in validatedChoices"
                                        :key="getChoiceKey(choice, choiceIndex)"
                                        class="flex-1 min-w-[100px] group p-3 border-2 rounded-lg transition-all duration-200 text-center min-h-[80px] flex flex-col justify-center hover:shadow-md"
                                        :class="{
                                            'bg-blue-100 border-blue-500 shadow-lg scale-105':
                                                answerStatus[currentIndex]?.selected ===
                                                choice.label,
                                            'border-gray-200 hover:border-blue-300 hover:bg-blue-50':
                                                answerStatus[currentIndex]?.selected !==
                                                choice.label,
                                        }"
                                        @click="handleAnswer(choice.label)"
                                    >
                                        <!-- 第一部:選択肢テキスト -->
                                        <div
                                            v-if="currentPart === 1 && choice.text"
                                            class="text-2xl leading-relaxed text-gray-700"
                                        >
                                            {{ choice.text }}
                                        </div>

                                        <!-- 第二部:選択肢画像 -->
                                        <div
                                            v-if="currentPart === 2"
                                            class="flex justify-center items-center flex-1"
                                        >
                                            <div v-if="shouldShowChoiceImage(choice)">
                                                <img
                                                    v-if="
                                                        choice.image &&
                                                        getImagePath(choice.image, 'choices')
                                                    "
                                                    :src="getImagePath(choice.image, 'choices')"
                                                    class="max-w-[80px] max-h-[50px] object-contain rounded border shadow-sm"
                                                    :alt="`選択肢${choice.label}`"
                                                    @error="handleImageError"
                                                    @load="handleImageLoad"
                                                />
                                                <div
                                                    v-else
                                                    class="flex items-center justify-center w-[80px] h-[50px] bg-gray-100 rounded border-2 border-dashed border-gray-300"
                                                >
                                                    <div class="text-center text-gray-400 text-xs">
                                                        <div>
                                                            {{ choice.image || "画像なし" }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- 画像がない場合の警告 -->
                                            <div
                                                v-if="!choice.image"
                                                class="flex items-center justify-center w-[80px] h-[50px] bg-red-50 rounded border-2 border-dashed border-red-300"
                                            >
                                                <div class="text-center text-red-500 text-xs">
                                                    <div>⚠️</div>
                                                    <div>画像データなし</div>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 下段:回答状況とナビゲーションボタン -->
                        <div class="flex justify-between items-start gap-4">
                            <!-- 左下:回答状況の表 -->
                            <div class="bg-gray-50 border rounded p-3 w-1/2">
                                <h3 class="font-semibold mb-3 text-sm">回答状況</h3>
                                <div
                                    class="overflow-x-auto"
                                    style="max-height: 300px; overflow-y: auto"
                                >
                                    <table
                                        class="table-auto border-collapse border border-gray-300 text-center w-full text-xs"
                                    >
                                        <thead class="bg-gray-100 sticky top-0 z-20">
                                            <tr class="bg-gray-100">
                                                <th
                                                    class="bg-gray-100 px-2 py-2 w-8 text-xs align-middle border border-gray-300"
                                                >
                                                    番号
                                                </th>
                                                <th
                                                    class="bg-gray-100 px-2 py-2 w-8 text-xs align-middle border border-gray-300"
                                                >
                                                    回答
                                                </th>
                                                <th
                                                    class="bg-gray-100 px-2 py-2 w-8 text-xs align-middle border border-gray-300"
                                                ></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(ans, idx) in answerStatus"
                                                :key="ans.questionNumber"
                                                :style="{
                                                    backgroundColor:
                                                        idx === currentIndex && !ans.checked
                                                            ? '#DBEAFE'
                                                            : ans.checked
                                                              ? '#FEE2E2'
                                                              : '',
                                                }"
                                            >
                                                <td
                                                    class="border px-1 py-2 cursor-pointer transition-colors text-xs hover:bg-blue-200"
                                                    :style="{
                                                        backgroundColor:
                                                            idx === currentIndex && !ans.checked
                                                                ? '#DBEAFE'
                                                                : ans.checked
                                                                  ? '#FEE2E2'
                                                                  : '',
                                                    }"
                                                    @click="jumpToQuestion(idx)"
                                                >
                                                    {{ ans.questionNumber }}
                                                </td>
                                                <td
                                                    class="border px-1 py-2 text-xs"
                                                    :style="{
                                                        backgroundColor:
                                                            idx === currentIndex && !ans.checked
                                                                ? '#DBEAFE'
                                                                : ans.checked
                                                                  ? '#FEE2E2'
                                                                  : '',
                                                    }"
                                                >
                                                    {{ ans.selected || "-" }}
                                                </td>
                                                <td
                                                    class="border px-1 py-2"
                                                    :style="{
                                                        backgroundColor:
                                                            idx === currentIndex && !ans.checked
                                                                ? '#DBEAFE'
                                                                : ans.checked
                                                                  ? '#FEE2E2'
                                                                  : '',
                                                    }"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        v-model="ans.checked"
                                                        class="form-checkbox w-4 h-4"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 右下:ナビゲーションボタン -->
                            <div class="w-1/2 flex gap-4 justify-center items-center">
                                <button
                                    class="px-12 py-4 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors text-xl font-bold"
                                    :disabled="currentIndex === 0"
                                    @click="prevQuestion"
                                >
                                    前の問題
                                </button>
                                <button
                                    class="px-12 py-4 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors text-xl font-bold"
                                    :disabled="currentIndex === questions.length - 1"
                                    @click="nextQuestion"
                                >
                                    次の問題
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 第三部:縦並びレイアウト -->
                    <div v-else class="mb-6">
                        <!-- 問題エリア -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mb-4">
                            <!-- 問題番号 -->
                            <div class="mb-4 text-left">
                                <span class="text-2xl font-bold text-gray-800"
                                    >問 {{ currentQuestion.number }}</span
                                >
                            </div>

                            <!-- 問題文 -->
                            <div class="min-h-[81px] flex items-start" v-if="currentQuestion.text">
                                <p class="text-xl leading-relaxed text-gray-700 mb-4">
                                    {{ currentQuestion.text }}
                                </p>
                            </div>
                        </div>

                        <!-- 選択肢エリア(横並び) -->
                        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mb-4">
                            <div class="mb-4">
                                <h3
                                    class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2"
                                >
                                    以下から正しい答えを選んでください
                                </h3>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button
                                    v-for="(choice, choiceIndex) in validatedChoices"
                                    :key="getChoiceKey(choice, choiceIndex, 'part3')"
                                    class="flex-1 min-w-0 p-6 border-2 rounded-lg transition-all duration-200 text-center hover:shadow-md flex flex-col items-center justify-center"
                                    :class="{
                                        'bg-blue-100 border-blue-500 shadow-lg':
                                            answerStatus[currentIndex]?.selected === choice.label,
                                        'border-gray-200 hover:border-blue-300 hover:bg-blue-50':
                                            answerStatus[currentIndex]?.selected !== choice.label,
                                    }"
                                    @click="handleAnswer(choice.label)"
                                >
                                    <!-- 選択肢テキストのみ(記号なし・文字大き) -->
                                    <div
                                        v-if="choice.text"
                                        class="text-2xl leading-relaxed font-medium text-gray-700"
                                    >
                                        {{ choice.text }}
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- 下部:回答状況と ナビゲーションボタンを横並び -->
                        <div class="flex justify-between items-start mt-4 gap-4">
                            <!-- 左側:回答状況(幅を広げて表示) -->
                            <div class="bg-gray-50 border rounded p-3 w-1/2">
                                <h3 class="font-semibold mb-3 text-sm">回答状況</h3>
                                <div
                                    class="overflow-x-auto"
                                    style="max-height: 200px; overflow-y: auto"
                                >
                                    <table
                                        class="table-auto border-collapse border border-gray-300 text-center text-xs w-full"
                                    >
                                        <thead class="bg-gray-100 sticky top-0 z-20">
                                            <tr class="bg-gray-100">
                                                <th
                                                    class="border border-gray-300 px-2 py-2 w-8 text-xs align-middle bg-gray-100 border-b-2"
                                                >
                                                    番号
                                                </th>
                                                <th
                                                    class="border border-gray-300 px-2 py-2 w-8 text-xs align-middle bg-gray-100 border-b-2"
                                                >
                                                    回答
                                                </th>
                                                <th
                                                    class="border border-gray-300 px-2 py-2 w-8 text-xs align-middle bg-gray-100 border-b-2"
                                                >
                                                    チェック
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(ans, idx) in answerStatus"
                                                :key="ans.questionNumber"
                                                :style="{
                                                    backgroundColor:
                                                        idx === currentIndex && !ans.checked
                                                            ? '#DBEAFE'
                                                            : ans.checked
                                                              ? '#FEE2E2'
                                                              : '',
                                                }"
                                            >
                                                <td
                                                    class="border px-2 py-2 cursor-pointer transition-colors text-xs hover:bg-blue-200"
                                                    :style="{
                                                        backgroundColor:
                                                            idx === currentIndex && !ans.checked
                                                                ? '#DBEAFE'
                                                                : ans.checked
                                                                  ? '#FEE2E2'
                                                                  : '',
                                                    }"
                                                    @click="jumpToQuestion(idx)"
                                                >
                                                    {{ ans.questionNumber }}
                                                </td>
                                                <td
                                                    class="border px-2 py-2 text-xs"
                                                    :style="{
                                                        backgroundColor:
                                                            idx === currentIndex && !ans.checked
                                                                ? '#DBEAFE'
                                                                : ans.checked
                                                                  ? '#FEE2E2'
                                                                  : '',
                                                    }"
                                                >
                                                    {{ ans.selected || "-" }}
                                                </td>
                                                <td
                                                    class="border px-2 py-2"
                                                    :style="{
                                                        backgroundColor:
                                                            idx === currentIndex && !ans.checked
                                                                ? '#DBEAFE'
                                                                : ans.checked
                                                                  ? '#FEE2E2'
                                                                  : '',
                                                    }"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        v-model="ans.checked"
                                                        class="form-checkbox w-4 h-4"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 右側:ナビゲーションボタン(横並び・左寄せ) -->
                            <div class="flex gap-4 items-center justify-start flex-1 pl-8">
                                <button
                                    class="px-12 py-4 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors text-xl font-bold"
                                    :disabled="currentIndex === 0"
                                    @click="prevQuestion"
                                >
                                    前の問題
                                </button>
                                <button
                                    class="px-12 py-4 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition-colors text-xl font-bold"
                                    :disabled="currentIndex === questions.length - 1"
                                    @click="nextQuestion"
                                >
                                    次の問題
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
/* 選択された状態のアニメーション */
.scale-105 {
    transform: scale(1.02);
}

/* ホバー時のスムーズなトランジション */
button {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .flex.gap-4 {
        flex-direction: column;
        gap: 1rem;
    }

    .w-1\/2 {
        width: 100%;
    }

    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
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

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useForm, usePage, Head, router } from "@inertiajs/vue3";

// 型定義
type ChoiceType = {
    id: number;
    label: string;
    text?: string;
    image?: string | null;
    part?: "1" | "2" | "3" | 1 | 2 | 3;
    is_correct?: boolean;
};

type QuestionType = {
    id: number;
    number: number;
    part: number;
    text?: string;
    image?: string | null;
    choices: ChoiceType[];
    selected?: string | null;
};

type AnswerStatus = {
    checked: boolean;
    questionNumber: number;
    selected: string | null;
};

interface PageProps {
    auth: any;
    ziggy: any;
    practiceQuestions: QuestionType[];
    currentPart: number;
    partTime: number;
    practiceSessionId: string;
    isGuest?: boolean;
    [key: string]: any;
}

// Props定義
const props = defineProps<{
    practiceSessionId: string;
    part?: number;
    practiceQuestions?: any[];
    currentPart?: number;
    partTime?: number;
    remainingTime?: number;
    currentQuestion?: number;
    totalParts?: number;
    isGuest?: boolean;
}>();

const page = usePage<PageProps>();

// 現在のパート番号を取得
// Practice.vue の getCurrentPartValue 関数を修正

const getCurrentPartValue = (): number => {
    // ★ 最優先: propsから直接取得
    if (props.currentPart && /^[1-3]$/.test(props.currentPart.toString())) {
        console.log("currentPart from props:", props.currentPart);
        return parseInt(props.currentPart.toString());
    }

    // ★ 次優先: page.propsから取得
    const partFromPage = page.props.currentPart;
    if (partFromPage && /^[1-3]$/.test(partFromPage.toString())) {
        console.log("currentPart from page.props:", partFromPage);
        return parseInt(partFromPage.toString());
    }

    // URLパラメータから取得
    const urlParams = new URLSearchParams(window.location.search);
    const urlPart = urlParams.get("part");

    if (urlPart && /^[1-3]$/.test(urlPart)) {
        console.log("currentPart from URL params:", urlPart);
        return parseInt(urlPart);
    }

    // パスから取得
    const pathMatch = window.location.pathname.match(/\/practice\/(\d+)/);
    if (pathMatch && pathMatch[1] && /^[1-3]$/.test(pathMatch[1])) {
        console.log("currentPart from path:", pathMatch[1]);
        return parseInt(pathMatch[1]);
    }

    // その他のpropsから取得
    const partFromProps = props.part;
    if (partFromProps && /^[1-3]$/.test(partFromProps.toString())) {
        console.log("currentPart from props.part:", partFromProps);
        return parseInt(partFromProps.toString());
    }

    console.warn("currentPart not found, defaulting to 1");
    return 1;
};

const currentPart = ref<number>(getCurrentPartValue());

// ★ 追加: propsが変更されたら currentPart を更新
watch(
    () => props.currentPart,
    newPart => {
        if (newPart && /^[1-3]$/.test(newPart.toString())) {
            const parsedPart = parseInt(newPart.toString());
            if (parsedPart !== currentPart.value) {
                console.log("currentPart updated from props:", parsedPart);
                currentPart.value = parsedPart;
                initializeQuestions();
            }
        }
    },
    { immediate: true }
);
const questions = ref<QuestionType[]>([]);
const answerStatus = ref<AnswerStatus[]>([]);
const showPracticeStartPopup = ref(true);
const currentIndex = ref(0);
const showConfirm = ref(false);

// データ初期化
const initializeQuestions = () => {
    const allQuestions = page.props.practiceQuestions || [];
    const filteredQuestions = allQuestions.filter((q: QuestionType) => {
        return parseInt(q.part.toString()) === parseInt(currentPart.value.toString());
    });

    questions.value = filteredQuestions;
    answerStatus.value = questions.value.map((q, index) => ({
        checked: false,
        questionNumber: index + 1,
        selected: q.selected || null,
    }));
};

// ゲストモード判定
const isGuest = computed(() => !page.props.auth?.user || page.props.isGuest === true);

// Practice.vue の form 定義を以下のように修正

const form = useForm({
    _token: page.props.csrf_token || "", // ★ CSRF トークンを明示的に追加
    answers: {} as Record<number, string>,
    practiceSessionId: page.props.practiceSessionId || "",
    part: currentPart.value,
    startTime: Date.now(),
    endTime: 0,
    timeSpent: 0,
    totalQuestions: 0,
    isTimeout: false, // ★ 追加: タイムアウトフラグ
});

const currentQuestion = computed(() => questions.value[currentIndex.value] || {});

// 練習開始
function startPractice() {
    showPracticeStartPopup.value = false;
    form.startTime = Date.now();
}

// 選択肢のバリデーション
const validatedChoices = computed(() => {
    if (!currentQuestion.value.choices) {
        return [];
    }

    const choices = currentQuestion.value.choices;
    const validPartChoices = choices.filter(choice => {
        const choicePart = choice.part;
        if (choicePart === undefined || choicePart === null) {
            return false;
        }
        return parseInt(choicePart.toString()) === parseInt(currentPart.value.toString());
    });

    if (validPartChoices.length === 0) {
        return [];
    }

    const uniqueChoiceIds = new Set<number>();
    const uniqueChoices = validPartChoices.filter(choice => {
        if (!choice || !choice.id) {
            return false;
        }
        if (uniqueChoiceIds.has(choice.id)) {
            return false;
        }
        uniqueChoiceIds.add(choice.id);
        return true;
    });

    const uniqueLabels = new Set<string>();
    const finalChoices = uniqueChoices.filter(choice => {
        if (!choice.label) {
            return false;
        }
        if (uniqueLabels.has(choice.label)) {
            return false;
        }
        uniqueLabels.add(choice.label);
        return true;
    });

    finalChoices.sort((a, b) => a.label.localeCompare(b.label));
    return finalChoices;
});

const getChoiceKey = (choice: ChoiceType, index: number, prefix: string = "") => {
    const questionId = currentQuestion.value.id || 0;
    const choiceId = choice.id || index;
    const baseKey = prefix
        ? `${prefix}-${questionId}-${choiceId}-${index}`
        : `choice-${questionId}-${choiceId}-${index}`;
    return baseKey;
};

// タイマー関連
const remainingTime = ref<number>(
    page.props.remainingTime !== undefined ? page.props.remainingTime : page.props.partTime || 300
);

const timerDisplay = computed(() => {
    const minutes = Math.floor(remainingTime.value / 60)
        .toString()
        .padStart(2, "0");
    const seconds = (remainingTime.value % 60).toString().padStart(2, "0");
    return `${minutes}:${seconds}`;
});

let timer: ReturnType<typeof setInterval> | undefined;

// 画像パス生成
const getImagePath = (imageName: any, imageType: "questions" | "choices"): string => {
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
        const modules = import.meta.glob("./images/**/*", {
            eager: true,
            as: "url",
        });
        const imagePath = `./images/${imageType}/${trimmedName}`;

        if (modules[imagePath]) {
            return modules[imagePath];
        }

        // フォールバック: publicディレクトリから読み込む
        return `/images/${imageType}/${trimmedName}`;
    } catch (error) {
        // エラー時はpublicディレクトリのパスを返す
        return `/images/${imageType}/${trimmedName}`;
    }
};

const shouldShowQuestionImage = computed(() => {
    return (
        currentPart.value === 2 &&
        currentQuestion.value.image &&
        currentQuestion.value.image.trim() !== ""
    );
});

const shouldShowChoiceImage = (choice: ChoiceType) => {
    return currentPart.value === 2 && choice.image && choice.image.trim() !== "";
};

const handleImageLoad = (event: Event) => {
    const target = event.target as HTMLImageElement;
    console.log(`画像読み込み成功: ${target.src}`);
};

const handleImageError = (event: Event): void => {
    const target = event.target as HTMLImageElement;
    const imageName = target.src.split("/").pop() || "unknown";
    target.style.display = "none";

    const parent = target.parentElement;
    if (parent && !parent.querySelector(".image-error-msg")) {
        const errorMsg = document.createElement("div");
        errorMsg.className =
            "image-error-msg text-red-500 text-xs mt-1 p-2 bg-red-50 border border-red-200 rounded";
        errorMsg.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>画像が読み込めません: ${imageName}</span>
            </div>
        `;
        parent.appendChild(errorMsg);
    }
};

const showImagePlaceholder = (parent: HTMLElement | null, imageName: string) => {
    if (parent && !parent.querySelector(".image-placeholder")) {
        const placeholder = document.createElement("div");
        placeholder.className =
            "image-placeholder flex items-center justify-center h-32 bg-gray-100 rounded shadow border-2 border-dashed border-gray-300";
        placeholder.innerHTML = `
            <div class="text-center text-gray-500">
                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <div class="text-xs">画像: ${imageName}</div>
                <div class="text-xs text-gray-400 mt-1">ファイルが見つかりません</div>
            </div>
        `;
        parent.appendChild(placeholder);
    }
};

// 練習完了処理
// Practice.vue の completePractice 関数を以下のように修正

const completePractice = () => {
    console.log("=== completePractice 呼び出し ===");
    console.log("isTimeout:", form.isTimeout);

    // タイマーを停止
    if (timer) {
        clearInterval(timer);
        timer = undefined;
    }

    form.practiceSessionId = props.practiceSessionId || page.props.practiceSessionId || "";
    form.part = currentPart.value;
    form.endTime = Date.now();
    form.timeSpent = Math.floor((Date.now() - form.startTime) / 1000);

    // ★ CSRF トークンを明示的に設定（meta タグから取得）
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    form._token = csrfToken;

    updateFormAnswers();

    console.log("=== 送信データの最終確認 ===");
    console.log("practiceSessionId:", form.practiceSessionId);
    console.log("part:", form.part);
    console.log("_token:", form._token?.substring(0, 20) + "...");
    console.log("answers:", form.answers);
    console.log("answersCount:", Object.keys(form.answers).length);
    console.log("timeSpent:", form.timeSpent);
    console.log("totalQuestions:", form.totalQuestions);
    console.log("isTimeout:", form.isTimeout); // ★ 追加
    console.log("========================");

    if (!form.practiceSessionId) {
        alert("セッションIDが不正です。ページを再読み込みしてください。");
        return;
    }

    if (!form.timeSpent || form.timeSpent <= 0) {
        form.timeSpent = 1;
    }

    if (!form.totalQuestions || form.totalQuestions === 0) {
        form.totalQuestions = questions.value.length;
        console.warn("totalQuestionsが0だったため、questions.lengthで上書き:", form.totalQuestions);
    }

    const routeName = isGuest.value ? "guest.practice.complete" : "practice.complete";
    const url = route(routeName);

    console.log("=== POST送信直前 ===");
    console.log("Route:", routeName);
    console.log("Data:", form.data());
    console.log("🔐 Inertia form.post() で送信");

    // ★ セッションクッキーが存在するか確認
    const cookies = document.cookie;
    const sessionCookie = cookies
        .split(";")
        .find(c => c.includes("XSRF-TOKEN") || c.includes("laravel_session"));
    console.log("🍪 Session Cookie:", sessionCookie ? "✅ PRESENT" : "❌ MISSING");
    console.log("Cookies:", cookies.substring(0, 100));

    // ★ CSRF トークン確保（ページ props から取得）
    const currentCsrfToken = page.props.csrf_token || "";
    form._token = currentCsrfToken;
    console.log("🔑 CSRF Token:", currentCsrfToken.substring(0, 20) + "...");

    // ★ フォーム送信関数
    const submitForm = () => {
        form.post(route(routeName), {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            preserveState: false,
            onSuccess: page => {
                console.log("✅ Practice completion successful");
                // PracticeExplanation ページへ移動（Inertia が自動リダイレクト）
            },
            onError: errors => {
                console.error("❌ Validation errors:", errors);
                const errorMessages = Object.values(errors).join(", ");
                alert(`バリデーションエラー: ${errorMessages}`);
            },
            onFinish: () => {
                console.log("Request finished");
            },
        });
    };

    // ★ CSRFトークンを更新してから送信（419エラー対策）
    if (typeof (window as any).forceRefreshCSRF === "function") {
        (window as any)
            .forceRefreshCSRF()
            .then(() => {
                console.log("CSRFトークン更新完了、フォーム送信開始");
                submitForm();
            })
            .catch(() => {
                console.log("CSRFトークン更新失敗、そのまま送信");
                submitForm();
            });
    } else {
        submitForm();
    }

    showConfirm.value = false;
};

// 回答処理
function handleAnswer(label: string) {
    const sanitizedLabel = String(label).trim().slice(0, 5);
    if (!/^[A-E]$/.test(sanitizedLabel)) {
        console.warn("Invalid answer label:", label);
        return;
    }

    console.log("=== handleAnswer ===");
    console.log("currentIndex:", currentIndex.value);
    console.log("selected answer:", sanitizedLabel);
    console.log("answerStatus length:", answerStatus.value.length);
    console.log("currentQuestion:", currentQuestion.value);

    if (currentIndex.value >= answerStatus.value.length) {
        console.error("Invalid currentIndex!");
        return;
    }

    answerStatus.value[currentIndex.value].selected = sanitizedLabel;
    console.log(
        "Updated answerStatus[" + currentIndex.value + "]:",
        answerStatus.value[currentIndex.value]
    );

    updateFormAnswers();
}

function updateFormAnswers() {
    const answers: Record<number, string> = {};

    // デバッグ: 回答状態の確認
    console.log("=== updateFormAnswers debug ===");
    console.log("answerStatus length:", answerStatus.value.length);
    console.log("questions length:", questions.value.length);

    answerStatus.value.forEach((ans, index) => {
        if (ans.selected) {
            const questionId = questions.value[index]?.id;
            if (questionId !== undefined) {
                answers[questionId] = ans.selected;
                console.log(`Question ${index}: ID=${questionId}, Answer=${ans.selected}`);
            } else {
                console.warn(`Question ${index}: ID is undefined!`);
            }
        }
    });

    form.answers = answers;
    form.totalQuestions = questions.value.length;

    console.log("Final answers object:", answers);
    console.log("Total questions:", form.totalQuestions);
    console.log("=== end debug ===");
}

function getAnsweredCount(): number {
    return answerStatus.value.filter(ans => ans.selected).length;
}

function prevQuestion() {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
}

function nextQuestion() {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++;
    }
}

function jumpToQuestion(idx: number) {
    if (idx >= 0 && idx < questions.value.length) {
        currentIndex.value = idx;
    }
}

function getCompleteButtonText(): string {
    return "練習完了";
}

function getConfirmMessage(): string {
    return "練習を完了して解説画面に進みますか?";
}

function confirmComplete() {
    showConfirm.value = false;
    completePractice();
}

// Practice.vue の handleTimeUp 関数を以下のように修正

function handleTimeUp() {
    console.log("=== タイムアップ発生 ===");
    console.log("remainingTime:", remainingTime.value);
    console.log("currentPart:", currentPart.value);

    // タイマーを停止
    if (timer) {
        clearInterval(timer);
        timer = undefined;
    }

    // ★ CSRF トークンを明示的に設定
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    form._token = csrfToken;

    // ★ 重要: isTimeout フラグを true に設定
    form.isTimeout = true;
    form.practiceSessionId = props.practiceSessionId || page.props.practiceSessionId || "";
    form.part = currentPart.value;
    form.endTime = Date.now();
    form.timeSpent = Math.floor((Date.now() - form.startTime) / 1000);
    form.totalQuestions = questions.value.length;

    // 回答データを更新（空でも送信）
    updateFormAnswers();

    console.log("=== タイムアップ時の送信データ ===");
    console.log("isTimeout:", form.isTimeout);
    console.log("_token:", form._token?.substring(0, 20) + "...");
    console.log("part:", form.part);
    console.log("practiceSessionId:", form.practiceSessionId);
    console.log("timeSpent:", form.timeSpent);
    console.log("answers:", form.answers);
    console.log("================================");

    const routeName = isGuest.value ? "guest.practice.complete" : "practice.complete";

    // アラート表示
    alert("制限時間が終了しました。自動的に次のパートに進みます。");

    // 直接POSTリクエストを送信
    form.post(route(routeName), {
        preserveState: false,
        preserveScroll: false,
        replace: false,
        forceFormData: false,
        onBefore: () => {
            console.log("=== タイムアップPOST送信直前 ===");
            console.log("Route:", routeName);
            console.log("Data:", form.data());
        },
        onSuccess: response => {
            console.log("タイムアップデータ送信成功:", response);
        },
        onError: errors => {
            console.error("タイムアップデータ送信エラー:", errors);
            alert("エラーが発生しました。ページをリロードしてください。");
        },
        onFinish: () => {
            console.log("タイムアップリクエスト処理完了");
        },
    });
}

// パート変更時のウォッチャー
watch(
    () => currentPart.value,
    () => {
        initializeQuestions();
        currentIndex.value = 0;
    }
);

// 4. タイマー処理を修正
onMounted(async () => {
    initializeQuestions();

    const startTimer = () => {
        if (!showPracticeStartPopup.value && !timer) {
            console.log("タイマー開始:", {
                remainingTime: remainingTime.value,
                part: currentPart.value,
            });

            timer = setInterval(() => {
                if (remainingTime.value > 0) {
                    remainingTime.value--;

                    // デバッグ: 残り10秒でログ出力
                    if (remainingTime.value <= 10) {
                        console.log(`残り時間: ${remainingTime.value}秒`);
                    }
                } else {
                    console.log("=== 時間切れ検出 ===");
                    if (timer) {
                        clearInterval(timer);
                        timer = undefined;
                    }
                    handleTimeUp();
                }
            }, 1000);
        } else if (showPracticeStartPopup.value) {
            setTimeout(startTimer, 100);
        }
    };

    startTimer();
});

// 5. コンポーネント破棄時の処理
onUnmounted(() => {
    console.log("Practice.vue アンマウント - タイマークリア");
    if (timer) {
        clearInterval(timer);
        timer = undefined;
    }
});
</script>
