<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";

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
const isGuest = computed(
    () => !page.props.auth?.user || page.props.isGuest === true
);

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

const getImagePath = (
    imageName: any,
    imageType: "questions" | "choices"
): string => {
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
    const hasValidExtension = validExtensions.some((ext) =>
        trimmedName.toLowerCase().endsWith(ext)
    );

    if (!hasValidExtension) {
        return "";
    }

    try {
        const imagePath = new URL(
            `./images/${imageType}/${trimmedName}`,
            import.meta.url
        ).href;
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

// 重要: GuestLayout と AuthenticatedLayout を使い分け
const layoutComponent = computed(() => {
    return isGuest.value ? GuestLayout : AuthenticatedLayout;
});

function getCurrentPart(): number {
    if (
        page.props.practiceQuestions &&
        page.props.practiceQuestions.length > 0
    ) {
        return page.props.practiceQuestions[0].part;
    }
    return page.props.currentPart || 1;
}

function goToExam() {
    const currentPart = getCurrentPart();
    isNavigating.value = true;

    console.log("=== goToExam 呼び出し ===");
    console.log("currentPart:", currentPart);
    console.log("isGuest:", isGuest.value);

    if (isGuest.value) {
        console.log("ゲストモード: 試験セッション作成中...");

        // ★ 修正: ゲスト用 - 既存セッションがあるか確認してから適切な部に進む
        router.visit(route("guest.exam.part", { part: currentPart }), {
            method: "get",
            preserveState: false,
            preserveScroll: false,
            replace: true,
            onBefore: () => {
                console.log("ゲスト本番試験ページへ遷移中...", {
                    part: currentPart,
                });
            },
            onFinish: () => {
                isNavigating.value = false;
                console.log("ゲスト試験開始処理完了");
            },
            onError: (errors) => {
                console.error("ゲスト試験開始エラー:", errors);
                isNavigating.value = false;
                alert("試験の開始に失敗しました。もう一度お試しください。");
            },
        });
    } else {
        console.log("認証ユーザー: 試験セッション作成中...");

        // ★ 修正: 認証ユーザー用 - 既存セッションがあるか確認してから適切な部に進む
        router.visit(route("exam.part", { part: currentPart }), {
            method: "get",
            preserveState: false,
            preserveScroll: false,
            replace: true,
            onBefore: () => {
                console.log("本番試験ページへ遷移中...", { part: currentPart });
            },
            onFinish: () => {
                isNavigating.value = false;
                console.log("認証ユーザー試験開始処理完了");
            },
            onError: (errors) => {
                console.error("本番試験開始エラー:", errors);
                isNavigating.value = false;
                alert("試験の開始に失敗しました。もう一度お試しください。");
            },
        });
    }
}

function getChoiceClass(
    choice: ChoiceType,
    question: PracticeQuestionType
): string {
    const classes = ["border-gray-300"];

    const choiceLabel = String(choice.label).trim();
    const questionAnswer = String(question.answer).trim();
    const selectedAnswer = question.selected
        ? String(question.selected).trim()
        : null;

    if (choiceLabel === questionAnswer) {
        classes.push("bg-green-100", "border-green-400");
        if (selectedAnswer === questionAnswer) {
            classes.push("bg-green-200", "border-green-500", "shadow-md");
        }
    } else if (
        selectedAnswer === choiceLabel &&
        choiceLabel !== questionAnswer
    ) {
        classes.push("bg-red-100", "border-red-400");
    } else {
        classes.push("bg-white", "hover:bg-gray-50");
    }

    return classes.join(" ");
}
</script>

<template>
    <!-- レイアウトコンポーネントの動的切り替え -->
    <component :is="layoutComponent">
        <Head title="解説ページ" />

        <div class="min-h-screen bg-gray-100">
            <!-- 上部ヘッダー -->
            <div
                class="max-w-7xl mx-auto flex justify-between items-center p-4 bg-white border-b shadow-sm"
            >
                <h2 class="text-xl font-bold text-gray-800">
                    解説　第{{
                        getCurrentPart()
                    }}部には次のような問題があります。
                </h2>
                <button
                    @click="goToExam"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-semibold shadow-md hover:shadow-lg"
                    :disabled="isNavigating"
                >
                    {{ isNavigating ? "移動中..." : "本番へ" }}
                </button>
            </div>

            <!-- 問題リスト -->
            <div class="max-w-7xl mx-auto p-6 space-y-6">
                <div
                    v-for="(q, idx) in processedQuestions"
                    :key="q.id"
                    class="bg-white border border-gray-200 rounded-lg shadow-sm p-6"
                >
                    <!-- 上段:問題番号と部ごとの説明文 -->
                    <div class="flex justify-between items-start mb-6">
                        <!-- 左:問題番号 -->
                        <div class="text-lg font-bold text-gray-800">
                            問題 {{ q.number }}
                        </div>

                        <!-- 中央:部の説明文 -->
                        <div
                            class="flex-1 ml-6 text-left text-base font-medium text-gray-600 bg-blue-50 p-3 rounded-lg border border-blue-200"
                        >
                            <template v-if="q.part === 1">
                                いくつかの文字が一定の規則に従って並んでいます。あなたはその規則を見つけ出し、配列を完成させて下さい。
                            </template>
                            <template v-else-if="q.part === 2">
                                各列の左側にある四つの図は一定の順序で並んでいます。次にくるべき図はどれでしょうか。右側の五つの図の中から選んで下さい。
                            </template>
                            <template v-else>
                                問題の下側に解答が5つありますが、正解は一つだけです。問題を解いてみて正しいと思う答えを選んでください。
                            </template>
                        </div>
                    </div>

                    <!-- 中段:問題文と選択肢(第1部・第2部) -->
                    <div
                        class="flex gap-6 mb-6"
                        v-if="q.part === 1 || q.part === 2"
                    >
                        <!-- 左:問題文と問題画像 -->
                        <div class="flex-1">
                            <p class="text-lg font-bold mb-4 text-gray-800">
                                {{ q.text }}
                            </p>
                            <!-- 問題画像を追加 -->
                            <div
                                v-if="
                                    q.image &&
                                    getImagePath(q.image, 'questions')
                                "
                                class="mt-4"
                            >
                                <img
                                    :src="getImagePath(q.image, 'questions')"
                                    class="w-full max-w-sm h-auto rounded-lg shadow-md border border-gray-200"
                                    :alt="`問題${q.number}`"
                                    @error="handleImageError"
                                    @load="handleImageLoad"
                                />
                            </div>
                        </div>

                        <!-- 右:選択肢 -->
                        <div class="flex-1 grid grid-cols-2 gap-3">
                            <div
                                v-for="choice in q.choices"
                                :key="choice.id"
                                class="border-2 rounded-lg p-3 transition-all duration-200"
                                :class="getChoiceClass(choice, q)"
                            >
                                <div class="font-semibold text-base mb-2">
                                    {{ choice.text }}
                                </div>
                                <!-- 選択肢画像を修正 -->
                                <img
                                    v-if="
                                        choice.image &&
                                        getImagePath(choice.image, 'choices')
                                    "
                                    :src="getImagePath(choice.image, 'choices')"
                                    class="mt-2 max-w-[120px] max-h-[80px] object-contain rounded border shadow-sm"
                                    :alt="`選択肢${choice.label}`"
                                    @error="handleImageError"
                                    @load="handleImageLoad"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 第三部:縦並び -->
                    <div v-else class="flex flex-col gap-4 mb-6">
                        <p class="text-lg font-bold mb-4 text-gray-800">
                            {{ q.text }}
                        </p>
                        <!-- 問題画像を追加 -->
                        <div
                            v-if="q.image && getImagePath(q.image, 'questions')"
                            class="mb-4"
                        >
                            <img
                                :src="getImagePath(q.image, 'questions')"
                                class="w-full max-w-lg h-auto rounded-lg shadow-md border border-gray-200"
                                :alt="`問題${q.number}`"
                                @error="handleImageError"
                                @load="handleImageLoad"
                            />
                        </div>
                        <div class="grid grid-cols-1 gap-3">
                            <div
                                v-for="choice in q.choices"
                                :key="choice.id"
                                class="border-2 rounded-lg p-4 transition-all duration-200"
                                :class="getChoiceClass(choice, q)"
                            >
                                <div class="font-semibold text-base">
                                    {{ choice.text }}
                                </div>
                                <!-- 選択肢画像を修正 -->
                                <img
                                    v-if="
                                        choice.image &&
                                        getImagePath(choice.image, 'choices')
                                    "
                                    :src="getImagePath(choice.image, 'choices')"
                                    class="mt-2 max-w-[120px] max-h-[80px] object-contain rounded border shadow-sm"
                                    :alt="`選択肢${choice.label}`"
                                    @error="handleImageError"
                                    @load="handleImageLoad"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- 判定結果 -->
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-gray-800">判定:</span>
                            <span v-if="q.selected && q.selected.trim()">
                                <span
                                    v-if="q.selected.trim() === q.answer.trim()"
                                    class="text-green-600 font-bold text-xl"
                                >
                                    ✓ 正解!
                                </span>
                                <span
                                    v-else
                                    class="text-red-600 font-bold text-xl"
                                >
                                    ✗ 不正解
                                </span>
                                <span class="ml-4 text-gray-700 text-base">
                                    あなたの回答: {{ q.selected.trim() }} |
                                    正解: {{ q.answer.trim() }}
                                </span>
                            </span>
                            <span
                                v-else
                                class="text-gray-500 font-semibold text-base"
                            >
                                未回答
                                <span class="ml-4 text-gray-700">
                                    正解: {{ q.answer.trim() }}
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- 下段:解説 -->
                    <div
                        class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="font-bold text-blue-800 flex-shrink-0 text-base"
                                >解説:</span
                            >
                            <span
                                class="text-gray-700 leading-relaxed text-base"
                                >{{ q.explanation }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
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
