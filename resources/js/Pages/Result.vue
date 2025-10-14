<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { usePage, Head, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

interface PartResult {
    correct: number;
    incorrect: number;
    unanswered: number;
    total: number;
    score: number;
}

interface PageProps extends Record<string, any> {
    auth: any;
    ziggy: any;
    isGuest?: boolean;
    guestName?: string;
    guestSchool?: string;
    results: {
        1: PartResult;
        2: PartResult;
        3: PartResult;
    };
    totalScore: number;
    rank: string;
    rankName: string;
}

const page = usePage<PageProps>();
const certificateRef = ref<SVGSVGElement | null>(null);
const hasCleanedUp = ref(false);

const logoUrl = computed(() => "/images/YIC_logo.png");
const stampUrl = computed(() => "/images/stamp.gif");

const isGuest = computed(() => {
    const hasAuthUser = page.props.auth?.user != null;
    const isGuestProp = page.props.isGuest === true;
    return !hasAuthUser || isGuestProp;
});

const displayUserName = computed(() => {
    if (isGuest.value) {
        return page.props.guestName || "名無し";
    }
    return page.props.auth?.user?.name || "ユーザー";
});

const displaySchoolName = computed(() => {
    if (isGuest.value) {
        return page.props.guestSchool || "学校名未入力";
    }
    return "YIC情報ビジネス専門学校";
});

const calculateStars = (score: number, maxScore: number): number => {
    if (maxScore === 0) return 1;
    const calculatedValue = (score / maxScore) * 7;
    if (calculatedValue <= 0.5) {
        return 1;
    }
    return Math.min(8, Math.round(calculatedValue) + 1);
};

const part1Stars = computed(() => {
    if (!page.props.results || !page.props.results[1]) return 1;
    return calculateStars(
        page.props.results[1].score,
        page.props.results[1].total
    );
});

const part2Stars = computed(() => {
    if (!page.props.results || !page.props.results[2]) return 1;
    return calculateStars(
        page.props.results[2].score,
        page.props.results[2].total
    );
});

const part3Stars = computed(() => {
    if (!page.props.results || !page.props.results[3]) return 1;
    return calculateStars(
        page.props.results[3].score,
        page.props.results[3].total
    );
});

const rankName = computed(() => page.props.rankName || "Bronze");

const formattedDate = computed(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth() + 1;
    const day = now.getDate();
    const reiwaYear = year - 2018;
    return `令和${reiwaYear}年 ${month}月 ${day}日`;
});

const cleanupGuestData = async () => {
    if (!isGuest.value || hasCleanedUp.value) {
        return;
    }

    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        const response = await fetch(route("guest.cleanup"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken || "",
                Accept: "application/json",
            },
        });

        if (response.ok) {
            hasCleanedUp.value = true;
            console.log("ゲストデータをクリーンアップしました");
        } else {
            console.warn("クリーンアップに失敗しました:", response.status);
        }
    } catch (error) {
        console.error("クリーンアップエラー:", error);
    }
};

const handleRetake = async () => {
    if (isGuest.value) {
        await cleanupGuestData();
        router.visit(route("guest.info"));
    } else {
        router.post(route("exam.start"));
    }
};

const handleBackToPractice = async () => {
    if (isGuest.value) {
        await cleanupGuestData();
        router.visit(route("guest.practice.show", { section: 1 }));
    } else {
        router.visit(route("practice.show", { section: 1 }));
    }
};

const downloadAsPDF = () => {
    const printWindow = window.open("", "_blank");
    if (!printWindow) {
        alert(
            "ポップアップがブロックされました。ポップアップを許可してください。"
        );
        return;
    }

    const svgElement = certificateRef.value;
    if (!svgElement) {
        alert("証明書の生成に失敗しました");
        return;
    }

    const svgData = new XMLSerializer().serializeToString(svgElement);

    // ★ 修正: A4サイズに最適化されたHTML
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>修了証書</title>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 0;
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                html, body {
                    width: 210mm;
                    height: 297mm;
                    margin: 0;
                    padding: 0;
                    overflow: hidden;
                }
                body {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    background: white;
                }
                svg {
                    width: 210mm;
                    height: 297mm;
                    display: block;
                }
                @media print {
                    html, body {
                        width: 210mm;
                        height: 297mm;
                        overflow: hidden;
                    }
                    body {
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    svg {
                        width: 210mm;
                        height: 297mm;
                        page-break-after: avoid;
                        page-break-before: avoid;
                        page-break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            ${svgData}
        </body>
        </html>
    `);

    printWindow.document.close();

    // ★ 修正: 画像読み込み完了を待つ
    const images = printWindow.document.querySelectorAll("image");
    let loadedImages = 0;
    const totalImages = images.length;

    if (totalImages === 0) {
        // 画像がない場合はすぐに印刷
        setTimeout(() => {
            printWindow.print();
        }, 300);
    } else {
        // すべての画像が読み込まれるまで待つ
        images.forEach((img) => {
            img.addEventListener("load", () => {
                loadedImages++;
                if (loadedImages === totalImages) {
                    setTimeout(() => {
                        printWindow.print();
                    }, 300);
                }
            });

            // エラー時も処理を進める
            img.addEventListener("error", () => {
                loadedImages++;
                if (loadedImages === totalImages) {
                    setTimeout(() => {
                        printWindow.print();
                    }, 300);
                }
            });
        });

        // タイムアウト: 3秒経っても読み込まれない場合は強制的に印刷
        setTimeout(() => {
            if (loadedImages < totalImages) {
                console.warn(
                    "一部の画像が読み込まれませんでしたが、印刷を実行します"
                );
                printWindow.print();
            }
        }, 3000);
    }
};

onMounted(() => {
    console.log("=== Result.vue データ確認 ===");
    console.log("page.props:", page.props);
    console.log("results:", page.props.results);
    console.log("rankName:", page.props.rankName);
    console.log("totalScore:", page.props.totalScore);
    console.log("part1Stars:", part1Stars.value);
    console.log("part2Stars:", part2Stars.value);
    console.log("part3Stars:", part3Stars.value);
    console.log("isGuest:", isGuest.value);
    console.log("========================");
});

onBeforeUnmount(() => {
    if (isGuest.value && !hasCleanedUp.value) {
        cleanupGuestData();
    }
});
</script>

<template>
    <component :is="isGuest ? 'div' : AuthenticatedLayout">
        <Head title="修了証書" />

        <div
            class="flex flex-col items-center justify-center min-h-screen bg-gray-50 p-8"
        >
            <!-- ★ 修正: 印刷用の余白を削除 -->
            <div
                class="bg-white rounded-lg shadow-2xl p-8 mb-6 print:p-0 print:shadow-none"
            >
                <svg
                    ref="certificateRef"
                    width="595"
                    height="842"
                    viewBox="0 0 595 842"
                    xmlns="http://www.w3.org/2000/svg"
                    class="border border-gray-200 print:border-0"
                >
                    <!-- 背景 -->
                    <rect width="595" height="842" fill="#FFFEF9" />

                    <!-- 枠線 -->
                    <rect
                        x="20"
                        y="20"
                        width="555"
                        height="802"
                        fill="none"
                        stroke="#8B7355"
                        stroke-width="2"
                    />
                    <rect
                        x="25"
                        y="25"
                        width="545"
                        height="792"
                        fill="none"
                        stroke="#8B7355"
                        stroke-width="1"
                    />

                    <!-- タイトル -->
                    <text
                        x="297.5"
                        y="110"
                        font-size="56"
                        font-weight="bold"
                        text-anchor="middle"
                        fill="#2C2C2C"
                        font-family="'HGP行書体', 'MS PGothic', serif"
                        letter-spacing="8"
                    >
                        修了証書
                    </text>

                    <!-- 学校名 -->
                    <text
                        x="80"
                        y="180"
                        font-size="16"
                        fill="#4A4A4A"
                        font-family="'HGP行書体', 'MS PGothic', serif"
                    >
                        {{ displaySchoolName }}
                    </text>

                    <!-- 受験者名 -->
                    <text
                        x="297.5"
                        y="270"
                        font-size="42"
                        font-weight="bold"
                        text-anchor="middle"
                        fill="#1A1A1A"
                        font-family="'HGP行書体', 'MS PGothic', serif"
                        letter-spacing="4"
                    >
                        {{ displayUserName }}
                    </text>
                    <text
                        x="470"
                        y="270"
                        font-size="28"
                        text-anchor="start"
                        fill="#1A1A1A"
                        font-family="'HGP行書体', 'MS PGothic', serif"
                    >
                        殿
                    </text>

                    <!-- 試験名 -->
                    <text
                        x="80"
                        y="360"
                        font-size="20"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        プログラマー適性検査
                    </text>

                    <!-- ランク -->
                    <text
                        x="400"
                        y="360"
                        font-size="28"
                        font-weight="bold"
                        text-anchor="middle"
                        fill="#2C2C2C"
                        font-family="Broadway, 'Arial Black', sans-serif"
                    >
                        {{ rankName }}
                    </text>

                    <!-- 区切り線 -->
                    <line
                        x1="100"
                        y1="380"
                        x2="495"
                        y2="380"
                        stroke="#CCCCCC"
                        stroke-width="1"
                    />

                    <!-- 規則発見力 -->
                    <text
                        x="120"
                        y="430"
                        font-size="18"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        規則発見力:
                    </text>
                    <text
                        v-for="i in part1Stars"
                        :key="`p1-${i}`"
                        :x="270 + (i - 1) * 28"
                        y="430"
                        font-size="22"
                        fill="#FFD700"
                    >
                        ★
                    </text>

                    <!-- 空間把握力 -->
                    <text
                        x="120"
                        y="475"
                        font-size="18"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        空間把握力:
                    </text>
                    <text
                        v-for="i in part2Stars"
                        :key="`p2-${i}`"
                        :x="270 + (i - 1) * 28"
                        y="475"
                        font-size="22"
                        fill="#FFD700"
                    >
                        ★
                    </text>

                    <!-- 問題解決力 -->
                    <text
                        x="120"
                        y="520"
                        font-size="18"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        問題解決力:
                    </text>
                    <text
                        v-for="i in part3Stars"
                        :key="`p3-${i}`"
                        :x="270 + (i - 1) * 28"
                        y="520"
                        font-size="22"
                        fill="#FFD700"
                    >
                        ★
                    </text>

                    <!-- 区切り線 -->
                    <line
                        x1="100"
                        y1="550"
                        x2="495"
                        y2="550"
                        stroke="#CCCCCC"
                        stroke-width="1"
                    />

                    <!-- 証明文 -->
                    <text
                        x="100"
                        y="610"
                        font-size="16"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        あなたは本校にて、上記の成績を修めたこ
                    </text>
                    <text
                        x="100"
                        y="635"
                        font-size="16"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        とをここに証します。
                    </text>

                    <!-- 日付 -->
                    <text
                        x="100"
                        y="695"
                        font-size="16"
                        fill="#3A3A3A"
                        font-family="'HGS教科書体', 'MS PGothic', sans-serif"
                    >
                        {{ formattedDate }}
                    </text>

                    <!-- 学校ロゴ -->
                    <image
                        v-if="logoUrl"
                        :href="logoUrl"
                        x="50"
                        y="730"
                        width="180"
                        height="50"
                    />
                    <g v-else>
                        <rect
                            x="50"
                            y="730"
                            width="180"
                            height="50"
                            fill="#4A90E2"
                            rx="3"
                        />
                        <text
                            x="60"
                            y="750"
                            font-size="11"
                            fill="white"
                            font-weight="bold"
                        >
                            学校法人 YIC学院
                        </text>
                        <text
                            x="60"
                            y="770"
                            font-size="13"
                            fill="white"
                            font-weight="bold"
                        >
                            YIC情報ビジネス専門学校
                        </text>
                    </g>

                    <!-- 副校長サイン -->
                    <text
                        x="350"
                        y="765"
                        font-size="18"
                        fill="#2C2C2C"
                        font-family="'HGP行書体', 'MS PGothic', serif"
                    >
                        副校長　河津　道正
                    </text>

                    <!-- 印鑑 -->
                    <image
                        :href="stampUrl"
                        x="473"
                        y="738"
                        width="44"
                        height="44"
                    />
                </svg>
            </div>

            <!-- ボタン -->
            <button
                @click="downloadAsPDF"
                class="flex items-center gap-2 px-8 py-4 bg-blue-600 text-white text-lg font-semibold rounded-lg hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl mb-6 print:hidden"
            >
                <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                    ></path>
                </svg>
                修了証書をダウンロード (PDF)
            </button>

            <div class="flex gap-4 print:hidden">
                <button
                    @click="handleBackToPractice"
                    class="px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                >
                    練習問題に戻る
                </button>
                <button
                    @click="handleRetake"
                    class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                >
                    再受験する
                </button>
            </div>
        </div>
    </component>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }

    .print\:p-0 {
        padding: 0 !important;
    }

    .print\:shadow-none {
        box-shadow: none !important;
    }

    .print\:border-0 {
        border: 0 !important;
    }
}
</style>
