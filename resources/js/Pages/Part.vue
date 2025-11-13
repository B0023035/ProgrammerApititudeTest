<template>
    <div class="min-h-screen bg-gray-100">
        <Head :title="`本番試験 第${currentPart}部`" />

        <!-- 開始前ポップアップ - 赤色版 + 無制限対応 -->
        <div
            v-if="showPracticeStartPopup"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        >
            <div class="bg-white rounded-lg p-8 shadow-xl max-w-lg w-full mx-4">
                <div class="text-center mb-6">
                    <div class="text-2xl font-bold text-red-800 mb-2">
                        本番試験 第{{ currentPart }}部
                    </div>
                </div>

                <div class="space-y-4 mb-6">
                    <!-- 問題数表示 -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg
                                class="w-5 h-5 text-red-600"
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
                            <span class="font-semibold text-red-800">問題数</span>
                        </div>
                        <p class="text-red-700">
                            全部で<span class="font-bold text-xl">{{ questions.length }}問</span
                            >出題されます
                        </p>
                    </div>

                    <!-- 制限時間表示(無制限対応) -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg
                                class="w-5 h-5 text-red-600"
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
                            <span class="font-semibold text-red-800">制限時間</span>
                        </div>
                        <p class="text-red-700">
                            <!-- ★ 無制限の場合の表示 -->
                            <span v-if="(page.props.partTime || 0) === 0" class="font-bold text-xl">
                                ∞ (無制限)
                            </span>
                            <span v-else class="font-bold text-xl">
                                {{ Math.floor((page.props.partTime || 300) / 60) }}分
                            </span>
                        </p>
                    </div>
                </div>

                <div class="text-center">
                    <button
                        @click="startPractice"
                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-8 rounded-lg transition-colors text-lg shadow-md"
                    >
                        試験を開始する
                    </button>
                </div>
            </div>
        </div>

        <!-- ナビゲーションバー -->
        <nav class="bg-white shadow" v-show="!showPracticeStartPopup">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold">本番試験システム</h1>
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
                <!-- 【修正】上部ヘッダー - 赤色に変更 -->
                <div
                    class="flex justify-between items-center mb-4 bg-red-50 border border-red-200 rounded p-3"
                >
                    <div class="flex items-center gap-4">
                        <div class="text-xl font-bold text-red-700">
                            本番試験 第{{ currentPart }}部
                        </div>
                        <div class="text-lg font-semibold text-red-600">
                            残り時間: {{ timerDisplay }}
                        </div>
                    </div>
                    <!-- 【修正】赤色に変更 -->
                    <button
                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
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
                            <!-- 【修正】赤色に変更 -->
                            <button
                                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
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
                    <!-- 【修正】部ごとの説明文 - 赤色に変更 -->
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="font-semibold text-red-800 text-base text-left">
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

                    <!-- 以下は既存のコードそのまま... -->

                    <!-- 第一部・第二部：3列レイアウト（問題文＋回答状況 | 選択肢） -->
                    <div
                        v-if="currentPart === 1 || currentPart === 2"
                        class="flex gap-4 mb-4 min-h-[400px]"
                    >
                        <!-- 左側：問題エリアと回答状況 -->
                        <div class="w-1/2 space-y-4">
                            <!-- 上部：問題エリア（サイズ縮小） -->
                            <div
                                class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm min-h-[200px]"
                            >
                                <!-- 問題番号 -->
                                <div class="mb-3 text-left">
                                    <span class="text-xl font-bold text-gray-800"
                                        >問 {{ currentQuestion.number }}</span
                                    >
                                </div>

                                <!-- 問題文 -->
                                <div v-if="currentQuestion.text" class="mb-4">
                                    <p class="text-xl leading-relaxed text-gray-800 font-medium">
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
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"
                                                    ></path>
                                                </svg>
                                                <div class="text-sm">画像を読み込み中...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 下部：回答状況の表（縦の高さ調整） -->
                            <div class="bg-gray-50 border rounded p-3 min-h-[220px]">
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
                                                class="hover:bg-gray-50"
                                                :class="{
                                                    'bg-yellow-100': idx === currentIndex,
                                                }"
                                            >
                                                <td
                                                    class="border px-1 py-1 cursor-pointer hover:bg-blue-100 transition-colors text-xs"
                                                    @click="jumpToQuestion(idx)"
                                                >
                                                    {{ ans.questionNumber }}
                                                </td>
                                                <td class="border px-1 py-1 text-xs">
                                                    {{ ans.selected || "-" }}
                                                </td>
                                                <td class="border px-1 py-1">
                                                    <input
                                                        type="checkbox"
                                                        v-model="ans.checked"
                                                        class="form-checkbox scale-75"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 右側：選択肢エリア -->
                        <div
                            class="w-1/2 bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex flex-col"
                        >
                            <div class="mb-3">
                                <h3
                                    class="text-base font-semibold text-gray-800 border-b border-gray-200 pb-2"
                                >
                                    選択肢を選んでください
                                </h3>
                            </div>

                            <!-- 選択肢ボタン -->
                            <div class="grid grid-cols-2 gap-3 flex-1">
                                <button
                                    v-for="(choice, choiceIndex) in validatedChoices"
                                    :key="getChoiceKey(choice, choiceIndex)"
                                    class="group p-3 border-2 rounded-lg transition-all duration-200 text-center min-h-[80px] flex flex-col justify-center hover:shadow-md"
                                    :class="{
                                        'bg-blue-100 border-blue-500 shadow-lg scale-105':
                                            answerStatus[currentIndex]?.selected === choice.label,
                                        'border-gray-200 hover:border-blue-300 hover:bg-blue-50':
                                            answerStatus[currentIndex]?.selected !== choice.label,
                                    }"
                                    @click="handleAnswer(choice.label)"
                                >
                                    <!-- 第一部：選択肢テキスト -->
                                    <div
                                        v-if="currentPart === 1 && choice.text"
                                        class="text-2xl leading-relaxed"
                                        :class="{
                                            'text-blue-800':
                                                answerStatus[currentIndex]?.selected ===
                                                choice.label,
                                            'text-gray-700':
                                                answerStatus[currentIndex]?.selected !==
                                                choice.label,
                                        }"
                                    >
                                        {{ choice.text }}
                                    </div>

                                    <!-- 第二部：選択肢画像 -->
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

                            <!-- 第一部・第二部のナビゲーションボタン（横並び） -->
                            <div class="flex gap-2 justify-center mt-4">
                                <button
                                    class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition-colors"
                                    :disabled="currentIndex === 0"
                                    @click="prevQuestion"
                                >
                                    前の問題
                                </button>
                                <button
                                    class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition-colors"
                                    :disabled="currentIndex === questions.length - 1"
                                    @click="nextQuestion"
                                >
                                    次の問題
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 第三部：縦並びレイアウト -->
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

                        <!-- 選択肢エリア（横並び） -->
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
                                    class="flex-1 min-w-0 p-4 border-2 rounded-lg transition-all duration-200 text-left hover:shadow-md flex flex-col items-center justify-center"
                                    :class="{
                                        'bg-blue-100 border-blue-500 shadow-lg':
                                            answerStatus[currentIndex]?.selected === choice.label,
                                        'border-gray-200 hover:border-blue-300 hover:bg-blue-50':
                                            answerStatus[currentIndex]?.selected !== choice.label,
                                    }"
                                    @click="handleAnswer(choice.label)"
                                >
                                    <div class="flex items-start gap-4">
                                        <div class="text-center">
                                            <!-- 選択肢ラベル（上部中央） -->
                                            <div
                                                class="text-2xl font-bold mb-3"
                                                :class="{
                                                    'text-blue-600':
                                                        answerStatus[currentIndex]?.selected ===
                                                        choice.label,
                                                    'text-gray-600':
                                                        answerStatus[currentIndex]?.selected !==
                                                        choice.label,
                                                }"
                                            >
                                                {{ choice.label }}
                                            </div>

                                            <!-- 選択肢テキスト（下部中央） -->
                                            <div
                                                v-if="choice.text"
                                                class="text-lg leading-relaxed"
                                                :class="{
                                                    'text-blue-800':
                                                        answerStatus[currentIndex]?.selected ===
                                                        choice.label,
                                                    'text-gray-700':
                                                        answerStatus[currentIndex]?.selected !==
                                                        choice.label,
                                                }"
                                            >
                                                {{ choice.text }}
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- 下部：回答状況と ナビゲーションボタンを横並び -->
                        <div class="flex justify-between items-start mt-4 gap-4">
                            <!-- 左側：回答状況（幅を広げて表示） -->
                            <div class="bg-gray-50 border rounded p-3 w-96">
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
                                                class="hover:bg-gray-50"
                                                :class="{
                                                    'bg-yellow-100': idx === currentIndex,
                                                }"
                                            >
                                                <td
                                                    class="border px-2 py-1 cursor-pointer hover:bg-blue-100 transition-colors text-xs"
                                                    @click="jumpToQuestion(idx)"
                                                >
                                                    {{ ans.questionNumber }}
                                                </td>
                                                <td class="border px-2 py-1 text-xs">
                                                    {{ ans.selected || "-" }}
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <input
                                                        type="checkbox"
                                                        v-model="ans.checked"
                                                        class="form-checkbox scale-75"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- 右側：ナビゲーションボタン（横並び・右寄せ） -->
                            <div class="flex gap-2 items-center">
                                <button
                                    class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition-colors"
                                    :disabled="currentIndex === 0"
                                    @click="prevQuestion"
                                >
                                    前の問題
                                </button>
                                <button
                                    class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition-colors"
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
import { ref, computed, onMounted, onUnmounted } from "vue";
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
    practiceSessionId?: string;
    examSessionId?: string; // この行を追加
    part?: number;
    practiceQuestions: any[];
    currentPart?: number;
    partTime?: number;
    remainingTime?: number;
    currentQuestion?: number;
    totalParts?: number;
    isGuest?: boolean;
}>();

const page = usePage<PageProps>();

// 重要：現在の部に該当する問題のみをフィルタリング
const getCurrentPartValue = (): number => {
    const urlParams = new URLSearchParams(window.location.search);
    const urlPart = urlParams.get("part");

    if (urlPart && /^[1-3]$/.test(urlPart)) {
        return parseInt(urlPart);
    }

    const pathMatch = window.location.pathname.match(/\/(\d+)/);
    if (pathMatch && pathMatch[1] && /^[1-3]$/.test(pathMatch[1])) {
        return parseInt(pathMatch[1]);
    }

    const partFromProps = props.part || props.currentPart;
    if (partFromProps && /^[1-3]$/.test(partFromProps.toString())) {
        return parseInt(partFromProps.toString());
    }

    const partFromPage = page.props.currentPart;
    if (partFromPage && /^[1-3]$/.test(partFromPage.toString())) {
        return parseInt(partFromPage.toString());
    }

    return 1;
};

const currentPart = ref<number>(getCurrentPartValue());

// 現在の部に対応する問題のみを取得
const filteredQuestions = computed(() => {
    const allQuestions = page.props.practiceQuestions || [];
    return allQuestions.filter((q: QuestionType) => q.part === currentPart.value);
});

const questions = ref<QuestionType[]>(filteredQuestions.value);

// ポップアップ制御
const showPracticeStartPopup = ref(true);
const currentIndex = ref(0);
const showConfirm = ref(false);

// ゲストモード判定
const isGuest = computed(() => !page.props.auth?.user || page.props.isGuest === true);

// フォーム設定
const form = useForm({
    answers: {} as Record<number, string>,
    examSessionId: page.props.examSessionId || page.props.practiceSessionId || "", // 追加
    practiceSessionId: page.props.practiceSessionId || "",
    part: currentPart.value,
    startTime: Date.now(),
    endTime: 0,
    timeSpent: 0,
    totalQuestions: 0,
});

const answerStatus = ref<AnswerStatus[]>(
    questions.value.map(q => ({
        checked: false,
        questionNumber: q.number,
        selected: q.selected || null,
    }))
);

const currentQuestion = computed(() => questions.value[currentIndex.value] || {});

// 練習開始関数
function startPractice() {
    showPracticeStartPopup.value = false;
    form.startTime = Date.now();
}

// 選択肢のバリデーションと重複排除（Part.vueと同じロジック）
const validatedChoices = computed(() => {
    if (!currentQuestion.value.choices) {
        console.log("No choices found for current question");
        return [];
    }

    const choices = currentQuestion.value.choices;
    console.log("Original choices:", choices);
    console.log("Current part:", currentPart.value);

    const validPartChoices = choices.filter(choice => {
        const choicePart = choice.part;
        console.log("Choice part:", choicePart, "Choice:", choice);

        if (choicePart === undefined || choicePart === null) {
            return false;
        }

        const normalizedChoicePart = parseInt(choicePart.toString());
        const normalizedCurrentPart = parseInt(currentPart.value.toString());

        return normalizedChoicePart === normalizedCurrentPart;
    });

    console.log("Valid part choices:", validPartChoices);

    if (validPartChoices.length === 0) {
        return [];
    }

    // 重複除去（IDベース）
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

    // ラベルの重複チェック
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

    // ラベル順でソート（A, B, C, D, E）
    finalChoices.sort((a, b) => a.label.localeCompare(b.label));

    console.log("Final choices:", finalChoices);
    return finalChoices;
});

// 安全なキー生成関数
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

// タイマー表示の計算プロパティ(無制限対応版)
const timerDisplay = computed(() => {
    // ★ 無制限時間(0分)の場合は特別表示
    if (remainingTime.value === 0) {
        return "∞ (無制限)";
    }

    const minutes = Math.floor(remainingTime.value / 60)
        .toString()
        .padStart(2, "0");
    const seconds = (remainingTime.value % 60).toString().padStart(2, "0");
    return `${minutes}:${seconds}`;
});

let timer: number | undefined;

// 画像パス生成関数
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
        const imagePath = new URL(`./images/${imageType}/${trimmedName}`, import.meta.url).href;

        return imagePath;
    } catch (error) {
        const fallbackPath = `/images/${imageType}/${trimmedName}`;
        return fallbackPath;
    }
};

// 画像を表示すべきかどうかを判定する計算プロパティ
const shouldShowQuestionImage = computed(() => {
    return (
        currentPart.value === 2 &&
        currentQuestion.value.image &&
        currentQuestion.value.image.trim() !== ""
    );
});

// 第二部の選択肢表示を画像のみに修正する関数
const shouldShowChoiceImage = (choice: ChoiceType) => {
    return currentPart.value === 2 && choice.image && choice.image.trim() !== "";
};

// 画像読み込み成功時の処理
const handleImageLoad = (event: Event) => {
    const target = event.target as HTMLImageElement;
    console.log(`画像読み込み成功: ${target.src}`);
};

// 画像エラーハンドリング
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

    if (!target.hasAttribute("data-retry-attempted")) {
        target.setAttribute("data-retry-attempted", "true");

        const altPaths = [
            `/images/questions/${imageName}`,
            `/images/choices/${imageName}`,
            `/storage/images/${imageName}`,
            `/assets/images/${imageName}`,
            `/img/${imageName}`,
        ];

        let retryIndex = 0;
        const tryNextPath = () => {
            if (retryIndex < altPaths.length) {
                target.src = altPaths[retryIndex];
                target.style.display = "block";

                target.addEventListener(
                    "error",
                    () => {
                        retryIndex++;
                        if (retryIndex < altPaths.length) {
                            setTimeout(tryNextPath, 500);
                        } else {
                            showImagePlaceholder(parent, imageName);
                        }
                    },
                    { once: true }
                );
            }
        };

        setTimeout(tryNextPath, 1000);
    }
};

// 画像プレースホルダー表示関数
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

const completePractice = () => {
    // セッションIDを複数のソースから取得を試みる
    let sessionId =
        props.practiceSessionId ||
        props.examSessionId ||
        page.props.practiceSessionId ||
        page.props.examSessionId ||
        "";

    console.log("=== セッションID確認 ===");
    console.log("props.practiceSessionId:", props.practiceSessionId);
    console.log("props.examSessionId:", props.examSessionId);
    console.log("page.props.practiceSessionId:", page.props.practiceSessionId);
    console.log("page.props.examSessionId:", page.props.examSessionId);
    console.log("最終的なsessionId:", sessionId);
    console.log("========================");

    // セッションIDの存在確認
    if (!sessionId || sessionId.trim() === "") {
        alert("セッションIDが見つかりません。ページを再読み込みしてください。");
        console.error("セッションIDが空です");
        return;
    }

    // 数値のみの場合はエラー
    if (/^\d+$/.test(sessionId)) {
        alert("セッションIDの形式が不正です(数値のみ)。ページを再読み込みしてください。");
        console.error("セッションIDが数値のみです:", sessionId);
        return;
    }

    // 最小長チェック(UUID = 36文字、ハイフン抜きでも32文字)
    if (sessionId.length < 32) {
        alert("セッションIDの形式が不正です。ページを再読み込みしてください。");
        console.error("セッションIDが短すぎます:", sessionId);
        return;
    }

    // 【修正】本番試験用にexamSessionIdとして設定
    form.examSessionId = sessionId; // これが重要！
    form.practiceSessionId = sessionId; // 互換性のため両方設定
    form.part = currentPart.value;
    form.endTime = Date.now();
    form.timeSpent = Math.floor((Date.now() - form.startTime) / 1000);
    form.totalQuestions = questions.value.length;

    // タイムスタンプの検証
    if (form.timeSpent < 0 || form.timeSpent > 7200) {
        alert("時間データが不正です。");
        console.error("timeSpent が範囲外:", form.timeSpent);
        return;
    }

    updateFormAnswers();

    // 回答数の検証
    if (Object.keys(form.answers).length > questions.value.length) {
        alert("回答データが不正です。");
        console.error("回答数が問題数を超えています");
        return;
    }

    // totalQuestionsが0の場合のフォールバック
    if (form.totalQuestions === 0) {
        alert("問題データが読み込まれていません。ページを再読み込みしてください。");
        console.error("totalQuestions is 0, questions:", questions.value);
        return;
    }

    // ★ 追加: 第三部の場合のみ answers テーブルに保存されることをログ出力
    if (currentPart.value === 3) {
        console.log("=== 第三部完了: answers テーブルに保存します ===");
    }

    // 【修正】送信データの構造を明確化
    const payload = {
        examSessionId: form.examSessionId, // 本番試験用
        practiceSessionId: form.practiceSessionId, // 互換性のため
        part: form.part,
        answers: form.answers,
        timeSpent: form.timeSpent,
        startTime: form.startTime,
        endTime: form.endTime,
        totalQuestions: form.totalQuestions,
    };

    console.log("=== 送信データの最終確認 ===");
    console.log("examSessionId:", payload.examSessionId);
    console.log("practiceSessionId:", payload.practiceSessionId);
    console.log("part:", payload.part);
    console.log("answers:", payload.answers);
    console.log("answersCount:", Object.keys(payload.answers).length);
    console.log("timeSpent:", payload.timeSpent);
    console.log("totalQuestions:", payload.totalQuestions);
    console.log("questions.length:", questions.value.length);
    console.log("========================");

    // ★ 追加: 完了前にキャッシュキーを記録
    if (currentPart.value === 3) {
        console.log("=== 第三部完了: キャッシュをクリアします ===");
    }

    // 本番試験の完了処理ルート
    const routeName = isGuest.value ? "guest.exam.complete-part" : "exam.complete-part";

    form.post(route(routeName), {
        onSuccess: () => {
            console.log("試験パート完了データ送信完了");
            // ★ 追加: 成功時にローカルストレージもクリア
            if (currentPart.value === 3) {
                // ブラウザのローカルストレージをクリア(もし使っていれば)
                try {
                    localStorage.removeItem(`exam_session_${sessionId}`);
                    localStorage.removeItem(`exam_answers_part_1`);
                    localStorage.removeItem(`exam_answers_part_2`);
                    localStorage.removeItem(`exam_answers_part_3`);
                    console.log("ローカルストレージをクリアしました");
                } catch (e) {
                    console.log("ローカルストレージのクリアは不要またはエラー");
                }
            }
        },
        onError: errors => {
            showConfirm.value = false;
            console.error("試験パート完了エラー:", errors);

            if (errors.examSessionId || errors.practiceSessionId) {
                alert(`セッションエラー: ${errors.examSessionId || errors.practiceSessionId}`);
            } else if (errors.timeSpent) {
                alert(`時間エラー: ${errors.timeSpent}`);
            } else if (errors.part) {
                alert(`パートエラー: ${errors.part}`);
            } else if (errors.totalQuestions) {
                alert(`問題数エラー: ${errors.totalQuestions}`);
            } else {
                const errorMessages = Object.keys(errors)
                    .map(key => `${key}: ${errors[key]}`)
                    .join("\n");
                alert(`エラーが発生しました:\n${errorMessages}`);
            }
        },
        onFinish: () => {
            console.log("リクエスト処理完了");
        },
    });
};

// handleAnswer 関数を修正（既存の関数を置き換え）
async function handleAnswer(label: string) {
    const sanitizedLabel = String(label).trim().slice(0, 5);
    if (!/^[A-E]$/.test(sanitizedLabel)) return;

    // 解答を記録
    answerStatus.value[currentIndex.value].selected = sanitizedLabel;
    updateFormAnswers();

    // サーバーに即座に保存
    await saveCurrentAnswer(sanitizedLabel);
}

// 現在の解答をサーバーに保存する関数
// Part.vue の saveCurrentAnswer 関数を修正

// Part.vue の saveCurrentAnswer 関数 - 完全版

const saveCurrentAnswer = async (choice: string, retryCount = 0) => {
    const MAX_RETRIES = 2;

    if (showPracticeStartPopup.value) return;

    // ★ ゲストはサーバー保存をスキップ(メモリのみ)
    if (isGuest.value) {
        console.log("ゲストモード: メモリのみ保存", {
            question: currentQuestion.value.id,
            choice: choice,
            part: currentPart.value,
        });
        return;
    }

    const currentQuestionId = currentQuestion.value.id;

    // ★★★ 追加: 問題IDの基本検証 ★★★
    if (!currentQuestionId || currentQuestionId <= 0) {
        console.warn("無効な問題ID:", currentQuestionId);
        return;
    }

    // ★★★ 追加: 問題が questions 配列に存在するか確認 ★★★
    const questionExists = questions.value.some(q => q.id === currentQuestionId);
    if (!questionExists) {
        console.warn("この問題はクエリ対象外です:", {
            questionId: currentQuestionId,
            part: currentPart.value,
            availableQuestions: questions.value.map(q => q.id),
            questionsCount: questions.value.length,
        });
        return; // ★ 保存をスキップ
    }

    try {
        // CSRFトークンを取得
        const getCsrfToken = () => {
            const metaToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            if (metaToken) return metaToken;

            const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            if (cookieMatch) {
                return decodeURIComponent(cookieMatch[1]);
            }

            return null;
        };

        let csrfToken = getCsrfToken();

        if (!csrfToken) {
            console.warn("CSRFトークンが見つかりません - スキップ");
            return; // ★ エラーにせずスキップ
        }

        console.log("=== リクエスト送信 ===", {
            url: route("exam.save-answer"),
            examSessionId: form.examSessionId,
            questionId: currentQuestionId,
            retryCount,
            part: currentPart.value,
            availableQuestions: questions.value.map(q => q.id),
        });

        const response = await fetch(route("exam.save-answer"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
            body: JSON.stringify({
                examSessionId: form.examSessionId,
                questionId: currentQuestionId,
                choice: choice,
                part: currentPart.value,
                remainingTime: remainingTime.value,
            }),
        });

        console.log("=== レスポンス受信 ===", {
            status: response.status,
            statusText: response.statusText,
            questionId: currentQuestionId,
        });

        // 419エラーの処理(リトライ回数制限付き)
        if (response.status === 419) {
            console.error(`419 CSRF Token Mismatch (試行 ${retryCount + 1}/${MAX_RETRIES + 1})`, {
                questionId: currentQuestionId,
                part: currentPart.value,
            });

            if (retryCount >= MAX_RETRIES) {
                console.warn("最大リトライ回数に達しました。解答は一時保存されています。", {
                    questionId: currentQuestionId,
                    choice: choice,
                });
                // ★ アラートを表示しない - サイレントに失敗
                return;
            }

            // CSRFトークンをリフレッシュ
            try {
                const refreshResponse = await fetch("/sanctum/csrf-cookie", {
                    method: "GET",
                    credentials: "same-origin",
                });

                if (refreshResponse.ok) {
                    console.log("CSRFトークンをリフレッシュしました");
                    await new Promise(resolve => setTimeout(resolve, 300));
                    return saveCurrentAnswer(choice, retryCount + 1);
                } else {
                    console.error("CSRFトークンのリフレッシュに失敗");
                }
            } catch (refreshError) {
                console.error("CSRFトークンのリフレッシュ中にエラー:", refreshError);
            }

            return;
        }

        if (!response.ok) {
            console.warn("解答保存に失敗:", response.status, response.statusText, {
                questionId: currentQuestionId,
                part: currentPart.value,
            });
            return; // ★ エラーにせず続行
        }

        const data = await response.json();

        if (data.success) {
            console.log("解答保存成功:", {
                question: currentQuestionId,
                choice: choice,
                part: currentPart.value,
            });
        } else {
            console.warn("解答保存に失敗:", data.message, {
                questionId: currentQuestionId,
            });
        }
    } catch (error) {
        console.error("解答保存エラー:", error, {
            questionId: currentQuestionId,
            part: currentPart.value,
        });
        // ★ アラートを表示せず、サイレントに失敗
    }
};

function updateFormAnswers() {
    const answers: Record<number, string> = {};
    answerStatus.value.forEach((ans, index) => {
        if (ans.selected) {
            answers[questions.value[index].id] = ans.selected;
        }
    });
    form.answers = answers;
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
    return currentPart.value < 3 ? `第${currentPart.value}部完了` : "試験完了";
}

function getConfirmMessage(): string {
    if (currentPart.value < 3) {
        return `第${currentPart.value}部の試験を完了して次の説明に進みますか?`;
    }
    return "試験を完了しますか?この操作は取り消せません。";
}

function confirmComplete() {
    showConfirm.value = false;
    completePractice();
}

function handleTimeUp() {
    alert("制限時間が終了しました。自動的に次のパートに進みます。");
    completePractice();
}

// 問題が変更された時にanswerStatusを更新
const updateAnswerStatus = () => {
    answerStatus.value = questions.value.map(q => ({
        checked: false,
        questionNumber: q.number,
        selected: q.selected || null,
    }));
};

onMounted(async () => {
    // 問題フィルタリング後にanswerStatusを更新
    updateAnswerStatus();

    // 無制限(partTime=0)の場合はタイマーを開始しない
    const partTimeLimit = page.props.partTime || 0;

    const startTimer = () => {
        if (!showPracticeStartPopup.value) {
            // 無制限の場合はタイマーをスキップ
            if (partTimeLimit === 0) {
                console.log("無制限時間モード: タイマーは動作しません");
                return;
            }

            // 時間制限がある場合のみタイマーを開始
            timer = setInterval(() => {
                if (remainingTime.value > 0) {
                    remainingTime.value--;
                } else {
                    handleTimeUp();
                }
            }, 1000);
        }
    };

    startTimer();
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>
