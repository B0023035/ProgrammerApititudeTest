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
                <!-- 上部ヘッダー - 赤色に変更 -->
                <div
                    class="flex justify-between items-center mb-4 bg-red-50 border border-red-200 rounded p-3"
                >
                    <div class="flex items-center gap-4">
                        <div class="text-xl font-bold text-red-700">
                            本番試験 第{{ currentPart }}部
                        </div>
                        <div
                            class="text-3xl font-bold transition-all duration-300"
                            :class="{
                                'text-red-600': remainingTime > 60,
                                'text-red-700 animate-pulse':
                                    remainingTime <= 60 && remainingTime > 0,
                                'text-red-700': remainingTime === 0,
                            }"
                        >
                            残り時間: {{ timerDisplay }}
                        </div>
                    </div>
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
                    <!-- 部ごとの説明文 - 赤色に変更 -->
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

                    <!-- 第一部・第二部:新しい4分割レイアウト -->
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
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"
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
                                    class="px-12 py-4 rounded-lg transition-colors text-xl font-bold"
                                    :class="
                                        currentIndex === 0
                                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                            : 'bg-gray-400 text-white hover:bg-gray-500'
                                    "
                                    :disabled="currentIndex === 0"
                                    @click="prevQuestion"
                                >
                                    前の問題
                                </button>
                                <button
                                    class="px-12 py-4 rounded-lg transition-colors text-xl font-bold"
                                    :class="
                                        currentIndex === questions.length - 1
                                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                            : 'bg-gray-400 text-white hover:bg-gray-500'
                                    "
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
                                        class="text-2xl leading-relaxed font-medium"
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
                                    class="px-12 py-4 rounded-lg transition-colors text-xl font-bold"
                                    :class="
                                        currentIndex === 0
                                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                            : 'bg-gray-400 text-white hover:bg-gray-500'
                                    "
                                    :disabled="currentIndex === 0"
                                    @click="prevQuestion"
                                >
                                    前の問題
                                </button>
                                <button
                                    class="px-12 py-4 rounded-lg transition-colors text-xl font-bold"
                                    :class="
                                        currentIndex === questions.length - 1
                                            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                            : 'bg-gray-400 text-white hover:bg-gray-500'
                                    "
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
.scale-105 {
    transform: scale(1.02);
}

button {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

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
    examSessionId?: string;
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

// CSRFトークンを取得（メタタグから）
const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    if (meta?.content) {
        return meta.content;
    }
    // フォールバック: propsから取得
    const token = page.props.ziggy?.csrf || page.props.csrf;
    return token || "";
};

// 現在の部を取得
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
    _token: (page.props as any).csrf_token || "", // ★ CSRF トークン
    answers: {} as Record<number, string>,
    examSessionId: page.props.examSessionId || page.props.practiceSessionId || "",
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

// ★★★ 改善版: バッチ保存用の変数 ★★★
const pendingAnswers = ref<Record<number, string>>({});
const lastSyncTime = ref(Date.now());
let syncTimer: number | undefined;

// 問題の遅延読み込み用
const loadedQuestionCount = ref(questions.value.length);
const totalQuestions = ref(questions.value.length);
const isLoadingMore = ref(false);

// 練習開始関数
function startPractice() {
    showPracticeStartPopup.value = false;
    form.startTime = Date.now();

    const partTimeLimit = page.props.partTime || 0;

    if (partTimeLimit > 0) {
        timer = setInterval(() => {
            if (remainingTime.value > 0) {
                remainingTime.value--;
                scheduleBatchSync(); // ★ タイマー減少時に定期保存
            } else {
                handleTimeUp();
            }
        }, 1000);

        console.log("タイマー開始", {
            partTimeLimit,
            remainingTime: remainingTime.value,
        });
    } else {
        console.log("無制限時間モード: タイマーは動作しません");
    }
}

// 選択肢のバリデーションと重複排除
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

const timerDisplay = computed(() => {
    const partTimeLimit = page.props.partTime || 0;

    if (partTimeLimit === 0) {
        return "∞ (無制限)";
    }

    const minutes = Math.floor(remainingTime.value / 60)
        .toString()
        .padStart(2, "0");
    const seconds = (remainingTime.value % 60).toString().padStart(2, "0");
    return `${minutes}:${seconds}`;
});

let timer: ReturnType<typeof setInterval> | undefined;

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

// ★★★ 変更1: syncAnswersToServer - バッチ保存関数(完全版) ★★★
/**
 * 未送信の回答をサーバーへバッチ送信
 * ✅ 解答選択時と1分ごとに呼ばれる
 */
async function syncAnswersToServer() {
    if (isGuest.value) {
        console.log("ゲストモード: サーバー同期をスキップ");
        return;
    }

    if (Object.keys(pendingAnswers.value).length === 0) {
        console.log("未送信の回答がありません");
        return;
    }

    const answersToSync = { ...pendingAnswers.value };
    const currentRemainingTime = remainingTime.value;

    console.log("=== バッチ同期開始 ===", {
        answers_count: Object.keys(answersToSync).length,
        remaining_time: currentRemainingTime,
        part: currentPart.value,
    });

    // 送信前にpendingAnswersをクリア
    pendingAnswers.value = {};

    try {
        const csrfToken = getCsrfToken();

        if (!csrfToken) {
            console.error("CSRFトークンが見つかりません - 認証エラーの可能性");
            console.log("メタタグおよびpropsからのトークン取得に失敗しました");
            pendingAnswers.value = { ...answersToSync, ...pendingAnswers.value };
            return;
        }

        const response = await fetch(route("exam.save-answers-batch"), {
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
                answers: answersToSync,
                part: currentPart.value,
                remainingTime: currentRemainingTime,
            }),
        });

        if (!response.ok) {
            console.warn("バッチ保存失敗:", response.status, response.statusText);

            if (response.status === 503) {
                console.log("サーバー混雑 - 5秒後にリトライします");
                pendingAnswers.value = { ...answersToSync, ...pendingAnswers.value };
                setTimeout(() => syncAnswersToServer(), 5000);
                return;
            } else if (response.status === 403 || response.status === 401) {
                console.error("認証エラー - セッションが切れた可能性があります");
                alert("セッションが切れました。ページを再読み込みしてください。");
                return;
            } else {
                pendingAnswers.value = { ...answersToSync, ...pendingAnswers.value };
                return;
            }
        }

        const data = await response.json();

        if (data.success) {
            console.log("✅ バッチ保存成功:", {
                answers_count: Object.keys(answersToSync).length,
                remaining_time: currentRemainingTime,
            });

            lastSyncTime.value = Date.now();
        } else {
            console.warn("バッチ保存失敗:", data.message);
            pendingAnswers.value = { ...answersToSync, ...pendingAnswers.value };
        }
    } catch (error) {
        console.error("バッチ保存エラー:", error);
        pendingAnswers.value = { ...answersToSync, ...pendingAnswers.value };

        if (error instanceof TypeError && error.message.includes("fetch")) {
            console.log("ネットワークエラー - 5秒後にリトライします");
            setTimeout(() => syncAnswersToServer(), 5000);
        }
    }
}

// ★★★ 変更2: handleAnswer - 解答選択時に即座保存 ★★★
/**
 * 回答選択時の処理
 * ✅ 解答選択時に即座にサーバーへ送信
 */
async function handleAnswer(label: string) {
    const sanitizedLabel = String(label).trim().slice(0, 5);
    if (!/^[A-E]$/.test(sanitizedLabel)) return;

    // 回答状態を更新
    answerStatus.value[currentIndex.value].selected = sanitizedLabel;

    // フォームの回答を更新
    updateFormAnswers();

    const questionId = currentQuestion.value.id;

    console.log("回答選択:", {
        question: questionId,
        choice: sanitizedLabel,
        is_guest: isGuest.value,
    });

    // ゲストモードの場合はローカル保存のみ
    if (isGuest.value) {
        console.log("ゲストモード: 回答をローカルにのみ保存");
        return;
    }

    // 認証ユーザー: pendingAnswers に追加
    pendingAnswers.value[questionId] = sanitizedLabel;

    console.log("🔥 解答選択時の即座保存:", {
        question: questionId,
        choice: sanitizedLabel,
        pending_count: Object.keys(pendingAnswers.value).length,
    });

    // 🔥 重要: 解答選択時に即座にサーバー送信
    await syncAnswersToServer();
}

// ============================================
// preloadNextBatch 関数の修正
// ============================================
// 既存の loadMoreQuestions 関数を削除して、
// この preloadNextBatch 関数に置き換えてください

/**
 * 次の問題バッチをプリロード - 改善版
 * ★既存の loadMoreQuestions を削除して、この関数に置き換え
 */
async function preloadNextBatch() {
    if (isLoadingMore.value || loadedQuestionCount.value >= totalQuestions.value) {
        return;
    }

    isLoadingMore.value = true;

    try {
        const response = await fetch(
            route("exam.questions-batch", {
                part: currentPart.value,
                offset: loadedQuestionCount.value,
            })
        );

        if (!response.ok) {
            console.error("問題取得失敗:", response.statusText);
            return;
        }

        const data = await response.json();

        if (data.questions && data.questions.length > 0) {
            questions.value.push(...data.questions);

            // ★変更1: loadedQuestionCount の更新方法を改善
            // 旧: loadedQuestionCount.value += data.questions.length;
            // 新: サーバーから返された loaded 値を使用
            loadedQuestionCount.value =
                data.loaded || loadedQuestionCount.value + data.questions.length;

            // ★変更2: totalQuestions の更新を追加
            if (data.total) {
                totalQuestions.value = data.total;
            }

            data.questions.forEach((q: QuestionType) => {
                answerStatus.value.push({
                    checked: false,
                    questionNumber: q.number,
                    selected: q.selected || null,
                });
            });

            console.log("問題バッチ読み込み成功:", {
                loaded: data.questions.length,
                total_loaded: loadedQuestionCount.value,
                total_questions: totalQuestions.value, // ★追加
                has_more: data.hasMore,
            });
        }
    } catch (error) {
        console.error("問題プリロードエラー:", error);
    } finally {
        isLoadingMore.value = false;
    }
}

const completePractice = () => {
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

    if (!sessionId || sessionId.trim() === "") {
        alert("セッションIDが見つかりません。ページを再読み込みしてください。");
        console.error("セッションIDが空です");
        return;
    }

    if (/^\d+$/.test(sessionId)) {
        alert("セッションIDの形式が不正です(数値のみ)。ページを再読み込みしてください。");
        console.error("セッションIDが数値のみです:", sessionId);
        return;
    }

    if (sessionId.length < 32) {
        alert("セッションIDの形式が不正です。ページを再読み込みしてください。");
        console.error("セッションIDが短すぎます:", sessionId);
        return;
    }

    form.examSessionId = sessionId;
    form.practiceSessionId = sessionId;
    form.part = currentPart.value;
    form.endTime = Date.now();
    form.timeSpent = Math.floor((Date.now() - form.startTime) / 1000);
    form.totalQuestions = questions.value.length;

    if (form.timeSpent < 0 || form.timeSpent > 7200) {
        alert("時間データが不正です。");
        console.error("timeSpent が範囲外:", form.timeSpent);
        return;
    }

    updateFormAnswers();

    if (Object.keys(form.answers).length > questions.value.length) {
        alert("回答データが不正です。");
        console.error("回答数が問題数を超えています");
        return;
    }

    if (form.totalQuestions === 0) {
        alert("問題データが読み込まれていません。ページを再読み込みしてください。");
        console.error("totalQuestions is 0, questions:", questions.value);
        return;
    }

    if (currentPart.value === 3) {
        console.log("=== 第三部完了: answers テーブルに保存します ===");
    }

    // ★ CSRF トークンを更新
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    form._token = csrfToken;

    const payload = {
        examSessionId: form.examSessionId,
        practiceSessionId: form.practiceSessionId,
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

    if (currentPart.value === 3) {
        console.log("=== 第三部完了: キャッシュをクリアします ===");
    }

    const routeName = isGuest.value ? "guest.exam.complete-part" : "exam.complete-part";

    // ★ 試験終了前にCSRFトークンを強制更新
    const submitForm = () => {
        form.post(route(routeName), {
            onSuccess: () => {
                console.log("試験パート完了データ送信完了");
                if (currentPart.value === 3) {
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

    // CSRFトークンを更新してから送信
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
};

function updateFormAnswers() {
    const answers: Record<number, string> = {};

    console.log("=== updateFormAnswers (Exam) ===");
    console.log("answerStatus length:", answerStatus.value.length);
    console.log("questions length:", questions.value.length);

    answerStatus.value.forEach((ans, index) => {
        if (ans.selected && questions.value[index]) {
            const questionId = questions.value[index].id;
            if (questionId !== undefined) {
                answers[questionId] = ans.selected;
                console.log(`Q${index}: ID=${questionId}, Answer=${ans.selected}`);
            } else {
                console.warn(`Q${index}: ID is undefined`);
            }
        }
    });

    form.answers = { ...answers, ...pendingAnswers.value };
    pendingAnswers.value = {};

    console.log("フォーム回答更新:", {
        answersCount: Object.keys(form.answers).length,
        totalQuestions: questions.value.length,
        answers: form.answers,
    });
}

function getAnsweredCount(): number {
    return answerStatus.value.filter(ans => ans.selected).length;
}

function prevQuestion() {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
}

// ============================================
// nextQuestion 関数の修正
// ============================================
// 既存の nextQuestion 関数を以下に置き換えてください

/**
 * 次の問題へ移動(改善版)
 * ★既存の nextQuestion 関数を削除して、この関数に置き換え
 */
function nextQuestion() {
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++;

        // ★追加: 残り3問になったら次のバッチをプリロード
        if (currentIndex.value >= loadedQuestionCount.value - 3) {
            preloadNextBatch();
        }
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

// ★★★ 変更3: handleTimeUp - 時間切れ処理(最終同期追加) ★★★
/**
 * 時間切れ処理
 * ✅ 未送信の回答があれば最後の同期を試みる
 */
function handleTimeUp() {
    console.log("=== 時間切れ処理開始 ===", {
        currentPart: currentPart.value,
        remainingTime: remainingTime.value,
        answersCount: Object.keys(form.answers).length,
        pendingCount: Object.keys(pendingAnswers.value).length,
    });

    if (timer) {
        clearInterval(timer);
        timer = undefined;
    }

    // 未送信の回答があれば最後の同期
    if (!isGuest.value && Object.keys(pendingAnswers.value).length > 0) {
        console.log("時間切れ前の最終同期", {
            pending_count: Object.keys(pendingAnswers.value).length,
        });

        // 同期的に送信を試みる(非推奨だが、時間切れ時は必要)
        const xhr = new XMLHttpRequest();
        xhr.open("POST", route("exam.save-answers-batch"), false); // 同期リクエスト
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.setRequestHeader("X-CSRF-TOKEN", page.props.ziggy?.csrf || "");
        xhr.send(
            JSON.stringify({
                examSessionId: form.examSessionId,
                answers: pendingAnswers.value,
                part: currentPart.value,
                remainingTime: 0,
            })
        );

        if (xhr.status === 200) {
            console.log("時間切れ時の最終同期成功");
            pendingAnswers.value = {};
        }
    }

    alert("制限時間が終了しました。自動的に次のパートに進みます。");

    try {
        completePractice();
    } catch (error) {
        console.error("時間切れ時の完了処理エラー:", error);
        alert("処理中にエラーが発生しましたが、次のパートに進みます。");

        const nextPart = currentPart.value < 3 ? currentPart.value + 1 : 3;
        const routeName = isGuest.value
            ? nextPart < 3
                ? "guest.practice.show"
                : "guest.result"
            : nextPart < 3
              ? "practice.show"
              : "exam.result";

        if (nextPart < 3) {
            window.location.href = route(routeName, { section: nextPart });
        } else {
            window.location.href = route(routeName);
        }
    }
}

const updateAnswerStatus = () => {
    answerStatus.value = questions.value.map(q => ({
        checked: false,
        questionNumber: q.number,
        selected: q.selected || null,
    }));
};

// ★★★ 変更4: handleBeforeUnload - ページ離脱時の処理 ★★★
/**
 * ページ離脱時のハンドラ
 * ✅ 未送信の回答があれば sendBeacon で送信
 */
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    // 試験中かつゲストでない場合のみ警告
    if (
        !isGuest.value &&
        remainingTime.value > 0 &&
        currentPart.value <= 3 &&
        !showPracticeStartPopup.value
    ) {
        // 未送信の回答がある場合は最後の同期を試みる
        if (Object.keys(pendingAnswers.value).length > 0) {
            console.log("ページ離脱前の緊急同期", {
                pending_count: Object.keys(pendingAnswers.value).length,
            });

            // sendBeacon APIで同期(より確実)
            const csrfToken = page.props.ziggy?.csrf;
            if (csrfToken) {
                const blob = new Blob(
                    [
                        JSON.stringify({
                            examSessionId: form.examSessionId,
                            answers: pendingAnswers.value,
                            part: currentPart.value,
                            remainingTime: remainingTime.value,
                            _token: csrfToken,
                        }),
                    ],
                    { type: "application/json" }
                );

                navigator.sendBeacon(route("exam.save-answers-batch"), blob);
            }
        }

        event.preventDefault();
        event.returnValue = "試験時間中にページを離れると、回答が失われる可能性があります。";
    }
};

/**
 * ★新規追加2: バッチ同期のスケジュール設定（デバウンス）
 * 配置場所: handleBeforeUnload の直後
 */
function scheduleBatchSync() {
    if (syncTimer) {
        clearTimeout(syncTimer);
    }

    // 30秒ごとに自動同期
    syncTimer = window.setInterval(async () => {
        if (!isGuest.value) {
            await syncAnswersToServer();
        }
    }, 30000);
}

// ★★★ 変更5: onMounted - 1分ごとの自動同期を統合 ★★★
/**
 * コンポーネントマウント時の処理
 * ✅ 1分(60秒)ごとに自動同期を実行
 */
onMounted(async () => {
    updateAnswerStatus();

    // 初期問題数が0の場合は読み込み
    if (questions.value.length === 0) {
        await preloadNextBatch();
    }

    const initialQuestionCount = page.props.practiceQuestions?.length || 0;
    if (initialQuestionCount > 0 && totalQuestions.value === 0) {
        totalQuestions.value = initialQuestionCount;
    }

    const partTimeLimit = page.props.partTime || 0;

    // タイマーを一本化(1分ごとの自動同期を統合)
    const startTimer = () => {
        if (!showPracticeStartPopup.value) {
            if (partTimeLimit === 0) {
                console.log("無制限時間モード: タイマーは動作しません");
                return;
            }

            timer = setInterval(() => {
                if (remainingTime.value > 0) {
                    remainingTime.value--;

                    // 🔥 1分(60秒)ごとに自動同期
                    if (
                        !isGuest.value &&
                        remainingTime.value % 60 === 0 &&
                        Object.keys(pendingAnswers.value).length > 0
                    ) {
                        console.log("🔥 1分ごとの自動同期トリガー", {
                            remaining_time: remainingTime.value,
                            pending_count: Object.keys(pendingAnswers.value).length,
                        });
                        syncAnswersToServer();
                    }
                } else {
                    handleTimeUp();
                }
            }, 1000);

            console.log("タイマー開始", {
                partTimeLimit,
                remainingTime: remainingTime.value,
            });
        }
    };

    startTimer();

    // beforeunloadイベントの登録
    window.addEventListener("beforeunload", handleBeforeUnload);

    console.log("=== コンポーネントマウント完了 ===", {
        questions_count: questions.value.length,
        total_questions: totalQuestions.value,
        part_time_limit: partTimeLimit,
        remaining_time: remainingTime.value,
        is_guest: isGuest.value,
    });
});

// ★★★ 変更6: onUnmounted - クリーンアップ処理 ★★★
/**
 * コンポーネントアンマウント時の処理
 * ✅ 未送信の回答があれば最後の同期を試みる
 */
onUnmounted(() => {
    console.log("=== コンポーネントアンマウント開始 ===", {
        has_pending_answers: Object.keys(pendingAnswers.value).length > 0,
        is_guest: isGuest.value,
    });

    // beforeunloadイベントリスナーの削除
    window.removeEventListener("beforeunload", handleBeforeUnload);

    // タイマーのクリア
    if (timer) {
        clearInterval(timer);
        timer = undefined;
    }

    // 最後の同期(ゲスト以外かつ未送信がある場合)
    if (!isGuest.value && Object.keys(pendingAnswers.value).length > 0) {
        console.log("アンマウント時の最終同期", {
            pending_count: Object.keys(pendingAnswers.value).length,
        });

        // 同期処理(非同期だが、できるだけ送信を試みる)
        syncAnswersToServer();
    }

    console.log("=== コンポーネントアンマウント完了 ===");
});
</script>
