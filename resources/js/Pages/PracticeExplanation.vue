<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage, router, useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";

// 型定義
type ChoiceType = {
    id: number;
    label: string;
    text: string;
    image?: string | null;
};

type PracticeQuestionType = {
    id: number;
    number: number;
    part: number;
    text: string;
    image?: string | null;
    choices: ChoiceType[];
    answer: string;
    selected?: string | null;
    explanation: string;
};

interface BasePageProps {
    auth: any;
    ziggy: any;
    [key: string]: any;
}

interface CustomPageProps extends BasePageProps {
    practiceQuestions: PracticeQuestionType[];
    answers?: string | Record<number, string>;
    isGuest?: boolean;
    currentPart?: number;
    timeSpent?: number;
    guestName?: string;
    guestSchool?: string;
}

const page = usePage<CustomPageProps>();
const isNavigating = ref(false);

// ゲスト判定
const isGuest = computed(() => !page.props.auth?.user || page.props.isGuest === true);

function getAnswersData(): Record<number, string> {
    let answersObj: Record<number, string> = {};

    if (page.props.answers && typeof page.props.answers === "object") {
        answersObj = page.props.answers;
        return answersObj;
    }

    if (page.props.answers && typeof page.props.answers === "string") {
        try {
            answersObj = JSON.parse(page.props.answers);
            return answersObj;
        } catch (e) {
            console.error("JSON文字列の解析に失敗:", e);
        }
    }

    return {};
}

const processedQuestions = computed(() => {
    const answersObj = getAnswersData();

    return page.props.practiceQuestions.map((q: PracticeQuestionType) => {
        let selectedAnswer = q.selected;

        if (!selectedAnswer && answersObj[q.number]) {
            selectedAnswer = String(answersObj[q.number]).trim();
        }

        return {
            ...q,
            selected: selectedAnswer || null,
        };
    });
});

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
        return `/images/${imageType}/${trimmedName}`;
    }
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

const handleImageLoad = (event: Event) => {
    const target = event.target as HTMLImageElement;
    console.log(`画像読み込み成功: ${target.src}`);
};

function getCurrentPart(): number {
    if (page.props.practiceQuestions && page.props.practiceQuestions.length > 0) {
        return page.props.practiceQuestions[0].part;
    }
    return page.props.currentPart || 1;
}

// ★ 修正: useFormを毎回新しく作成してCSRFトークンをリフレッシュ
function goToExam() {
    const currentPart = getCurrentPart();
    isNavigating.value = true;

    console.log("=== goToExam呼び出し ===");
    console.log("currentPart:", currentPart);
    console.log("isGuest:", isGuest.value);

    // ★ 重要: 毎回新しいフォームインスタンスを作成してCSRFトークンを更新
    const csrfToken = (page.value.props as any).csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    const freshForm = useForm({ _token: csrfToken });

    if (isGuest.value) {
        console.log(`ゲスト第${currentPart}部: guest.exam.start (form.post) で遷移`);
        freshForm.post(route("guest.exam.start"), {
            preserveState: false,
            preserveScroll: false,
            onBefore: () => {
                console.log(`guest.exam.start POST 送信前`);
            },
            onSuccess: (page: any) => {
                console.log(`guest.exam.start 成功、自動リダイレクト完了`);
            },
            onFinish: () => {
                isNavigating.value = false;
            },
            onError: (errors: any) => {
                console.error("本番試験開始エラー:", errors);
                isNavigating.value = false;

                // ★ CSRFエラーの場合は明示的にメッセージを表示
                if (errors.message && errors.message.includes("419")) {
                    alert("セッションの有効期限が切れました。ページを再読み込みしてください。");
                    window.location.reload();
                } else {
                    alert("本番試験の開始に失敗しました。セッションコードを確認してください。");
                }
            },
        });
    } else {
        console.log(`認証ユーザー第${currentPart}部: exam.start (form.post) で遷移`);
        freshForm.post(route("exam.start"), {
            preserveState: false,
            preserveScroll: false,
            onBefore: () => {
                console.log(`exam.start POST 送信前`);
            },
            onSuccess: (page: any) => {
                console.log(`exam.start 成功、自動リダイレクト完了`);
            },
            onFinish: () => {
                isNavigating.value = false;
            },
            onError: (errors: any) => {
                console.error("本番試験開始エラー:", errors);
                isNavigating.value = false;

                // ★ CSRFエラーの場合は明示的にメッセージを表示
                if (errors.message && errors.message.includes("419")) {
                    alert("セッションの有効期限が切れました。ページを再読み込みしてください。");
                    window.location.reload();
                } else {
                    alert("本番試験の開始に失敗しました。セッションコードを確認してください。");
                }
            },
        });
    }
}

function getChoiceClass(choice: ChoiceType, question: PracticeQuestionType): string {
    const classes = ["border-gray-300"];

    const choiceLabel = String(choice.label).trim();
    const questionAnswer = String(question.answer).trim();
    const selectedAnswer = question.selected ? String(question.selected).trim() : null;

    if (choiceLabel === questionAnswer) {
        classes.push("bg-green-100", "border-green-400");
        if (selectedAnswer === questionAnswer) {
            classes.push("bg-green-200", "border-green-500", "shadow-md");
        }
    } else if (selectedAnswer === choiceLabel && choiceLabel !== questionAnswer) {
        classes.push("bg-red-100", "border-red-400");
    } else {
        classes.push("bg-white", "hover:bg-gray-50");
    }

    return classes.join(" ");
}
</script>

<template>
    <div>
        <Head title="解説ページ" />

        <!-- 統一されたレイアウト(ゲスト・ログイン共通) -->
        <div class="min-h-screen bg-gray-100">
            <!-- ヘッダー(ゲストの場合のみ表示) -->
            <nav v-if="isGuest" class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <div class="flex items-center">
                            <img src="/images/YIC_logo.png" alt="YIC Logo" class="h-10" />
                            <span class="ml-4 text-lg font-semibold text-gray-800">
                                練習問題 解説
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">ゲストモード</div>
                    </div>
                </div>
            </nav>

            <!-- ログインユーザーの場合はAuthenticatedLayoutのヘッダーを表示 -->
            <nav v-else class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center">
                        <img src="/images/YIC_logo.png" alt="YIC Logo" class="h-10" />
                    </div>
                </div>
            </nav>

            <!-- メインコンテンツ -->
            <main>
                <div class="py-6">
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <!-- 上部ヘッダー -->
                        <div
                            class="flex justify-between items-center px-6 py-4 bg-white border-b shadow-sm rounded-lg mb-6"
                        >
                            <h2 class="text-2xl font-bold text-gray-800">
                                解説 第{{ getCurrentPart() }}部には次のような問題があります。
                            </h2>
                            <button
                                @click="goToExam"
                                class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-md hover:shadow-lg text-lg"
                                :disabled="isNavigating"
                            >
                                {{ isNavigating ? "移動中..." : "本番へ" }}
                            </button>
                        </div>

                        <!-- 問題リスト -->
                        <div class="space-y-8">
                            <div
                                v-for="(q, idx) in processedQuestions"
                                :key="q.id"
                                class="bg-white border border-gray-200 rounded-lg shadow-sm p-8"
                            >
                                <!-- 上段:問題番号と部ごとの説明文 -->
                                <div class="flex justify-between items-center mb-6">
                                    <!-- 左:問題番号 -->
                                    <div class="text-2xl font-bold text-gray-800 flex-shrink-0">
                                        問題 {{ q.number }}
                                    </div>

                                    <!-- 中央:部の説明文 -->
                                    <div
                                        class="flex-1 ml-8 text-left text-lg font-medium text-gray-700 bg-blue-50 p-4 rounded-lg border border-blue-200"
                                    >
                                        <template v-if="q.part === 1">
                                            いくつかの文字が一定の規則に従って並んでいます。あなたはその規則を見つけ出し、配列を完成させて下さい。
                                        </template>
                                        <template v-else-if="q.part === 2">
                                            各列の左側にある四つの図は一定の順序で並んでいます。次にくるべき図はどれでしょうか。右側の五つの図の中から選んで下さい。
                                        </template>
                                        <template v-else>
                                            問題の下側に解答が5つありますが、正解は一つだけです。問題を解いてみて正しいと思う答えを選んで下さい。
                                        </template>
                                    </div>
                                </div>

                                <!-- 中段:問題文と選択肢(第1部・第2部) -->
                                <div class="mb-6" v-if="q.part === 1 || q.part === 2">
                                    <!-- 第1部:問題文のみ -->
                                    <div v-if="q.part === 1">
                                        <p class="text-3xl font-bold mb-6 text-gray-800">
                                            {{ q.text }}
                                        </p>
                                        <!-- 選択肢(第1部) -->
                                        <div class="grid grid-cols-5 gap-3">
                                            <div
                                                v-for="choice in q.choices"
                                                :key="choice.id"
                                                class="border-2 rounded-lg p-3 transition-all duration-200 min-h-[100px] flex flex-col items-center justify-center"
                                                :class="getChoiceClass(choice, q)"
                                            >
                                                <div
                                                    class="font-semibold text-2xl text-center mb-2"
                                                >
                                                    {{ choice.text }}
                                                </div>
                                                <img
                                                    v-if="
                                                        choice.image &&
                                                        getImagePath(choice.image, 'choices')
                                                    "
                                                    :src="getImagePath(choice.image, 'choices')"
                                                    class="mt-2 max-w-full max-h-[60px] object-contain rounded border shadow-sm"
                                                    :alt="`選択肢${choice.label}`"
                                                    @error="handleImageError"
                                                    @load="handleImageLoad"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 第2部:問題画像と選択肢を横並び -->
                                    <div v-if="q.part === 2" class="flex gap-8">
                                        <!-- 左:問題画像 -->
                                        <div
                                            v-if="q.image && getImagePath(q.image, 'questions')"
                                            class="w-2/5"
                                        >
                                            <img
                                                :src="getImagePath(q.image, 'questions')"
                                                class="w-full h-auto rounded-lg shadow-md border border-gray-200"
                                                :alt="`問題${q.number}`"
                                                @error="handleImageError"
                                                @load="handleImageLoad"
                                            />
                                        </div>

                                        <!-- 右:選択肢(第2部) -->
                                        <div class="flex-1">
                                            <div class="grid grid-cols-5 gap-3">
                                                <div
                                                    v-for="choice in q.choices"
                                                    :key="choice.id"
                                                    class="border-2 rounded-lg p-3 transition-all duration-200 flex flex-col items-center justify-center aspect-square"
                                                    :class="getChoiceClass(choice, q)"
                                                >
                                                    <div
                                                        class="font-semibold text-2xl text-center mb-2"
                                                    >
                                                        {{ choice.text }}
                                                    </div>
                                                    <div
                                                        class="flex-1 flex items-center justify-center w-full"
                                                    >
                                                        <img
                                                            v-if="
                                                                choice.image &&
                                                                getImagePath(
                                                                    choice.image,
                                                                    'choices'
                                                                )
                                                            "
                                                            :src="
                                                                getImagePath(
                                                                    choice.image,
                                                                    'choices'
                                                                )
                                                            "
                                                            class="max-w-full max-h-[80px] object-contain rounded border shadow-sm"
                                                            :alt="`選択肢${choice.label}`"
                                                            @error="handleImageError"
                                                            @load="handleImageLoad"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 第三部:横5列並び -->
                                <div v-else class="mb-6">
                                    <p class="text-3xl font-bold mb-6 text-gray-800">
                                        {{ q.text }}
                                    </p>
                                    <div
                                        v-if="q.image && getImagePath(q.image, 'questions')"
                                        class="mb-6"
                                    >
                                        <img
                                            :src="getImagePath(q.image, 'questions')"
                                            class="w-full max-w-2xl h-auto rounded-lg shadow-md border border-gray-200"
                                            :alt="`問題${q.number}`"
                                            @error="handleImageError"
                                            @load="handleImageLoad"
                                        />
                                    </div>
                                    <div class="grid grid-cols-5 gap-4">
                                        <div
                                            v-for="choice in q.choices"
                                            :key="choice.id"
                                            class="border-2 rounded-lg p-4 transition-all duration-200 min-h-[100px] flex flex-col items-center justify-center"
                                            :class="getChoiceClass(choice, q)"
                                        >
                                            <div class="font-semibold text-2xl text-center mb-2">
                                                {{ choice.text }}
                                            </div>
                                            <img
                                                v-if="
                                                    choice.image &&
                                                    getImagePath(choice.image, 'choices')
                                                "
                                                :src="getImagePath(choice.image, 'choices')"
                                                class="mt-2 max-w-full max-h-[60px] object-contain rounded border shadow-sm"
                                                :alt="`選択肢${choice.label}`"
                                                @error="handleImageError"
                                                @load="handleImageLoad"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- 判定結果 -->
                                <div class="mb-6 p-5 bg-gray-50 rounded-lg border">
                                    <div class="flex items-center gap-4">
                                        <span class="font-bold text-gray-800 text-lg">判定:</span>
                                        <span v-if="q.selected && q.selected.trim()">
                                            <span
                                                v-if="q.selected.trim() === q.answer.trim()"
                                                class="text-green-600 font-bold text-2xl"
                                            >
                                                ✓ 正解!
                                            </span>
                                            <span v-else class="text-red-600 font-bold text-2xl">
                                                ✗ 不正解
                                            </span>
                                            <span class="ml-6 text-gray-700 text-lg">
                                                あなたの回答: {{ q.selected.trim() }} | 正解:
                                                {{ q.answer.trim() }}
                                            </span>
                                        </span>
                                        <span v-else class="text-gray-500 font-semibold text-lg">
                                            未回答
                                            <span class="ml-6 text-gray-700">
                                                正解: {{ q.answer.trim() }}
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- 下段:解説 -->
                                <div
                                    class="mt-6 p-5 bg-blue-50 border-l-4 border-blue-500 rounded-lg"
                                >
                                    <div class="flex items-start gap-4">
                                        <span class="font-bold text-blue-800 flex-shrink-0 text-lg"
                                            >解説:</span
                                        >
                                        <span class="text-gray-700 leading-relaxed text-lg">{{
                                            q.explanation
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- 本番試験への移動ボタン -->
            <div class="fixed bottom-6 right-6 flex gap-3">
                <button
                    @click="goToExam"
                    :disabled="isNavigating"
                    class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg shadow-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                    <span v-if="!isNavigating">本番試験へ進む →</span>
                    <span v-else>読み込み中...</span>
                </button>
            </div>

            <!-- フッター -->
            <footer class="bg-gray-400 text-white text-center py-4">
                © 2025 YIC Group. All rights reserved.
            </footer>
        </div>
    </div>
</template>

<style scoped>
.transition-all {
    transition: all 0.2s ease-in-out;
}

button:hover:not(:disabled) {
    transform: translateY(-1px);
}

button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
