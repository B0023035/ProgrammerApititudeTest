<template>
    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <Head title="検査説明" />

        <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-lg p-8">
            <!-- タイトル -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">プログラマー適性検査について</h1>
                <p class="text-gray-600">これから始める検査の説明をよくお読みください</p>
            </div>

            <div class="space-y-6 mb-8">
                <!-- 1) 検査開始について -->
                <div class="border-l-4 border-blue-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">
                        1) これからプログラマー適性検査 Excel 版を始めます。
                    </h2>
                </div>

                <!-- 2) 入力について -->
                <div class="border-l-4 border-blue-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">
                        2)
                        {{ isGuest ? "所属・名前の入力について" : "トップ画面での入力について" }}
                    </h2>
                    <p v-if="isGuest" class="text-gray-700 leading-relaxed">
                        適性検査終了後、結果を表示する際に所属・名前の入力が必要になります。<br />
                        検査結果の証明書に記載されますので、正確に入力してください。
                    </p>
                    <p v-else class="text-gray-700 leading-relaxed">
                        トップ画面で入力して頂いた所属・名前は、プログラマー適性検査の結果を表示する際に使用されます。<br />
                        もし、先ほどのトップ画面で所属・名前を入力していなくても、適性検査終了後に再度入力できますので、安心してください。
                    </p>
                </div>

                <!-- 3) 時間制限について -->
                <div class="border-l-4 border-blue-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">3) 時間制限について</h2>
                    <p class="text-gray-700 leading-relaxed">
                        時間に制限があります。各部の問題すべてには手が回らない事がありますが、気にしないでください。
                    </p>
                </div>

                <!-- 4) 得点計算について -->
                <div class="border-l-4 border-red-500 pl-4 bg-red-50 p-4 rounded">
                    <h2 class="text-xl font-semibold text-red-800 mb-2">
                        4) 得点計算について（重要）
                    </h2>
                    <p class="text-red-700 leading-relaxed font-medium">
                        貴方の得点は<span class="text-red-600 font-bold underline"
                            >正しい答えの数</span
                        >から、
                        <span class="text-red-600 font-bold underline"
                            >正しくない数の1/4を引いた値</span
                        >となります。
                    </p>
                </div>

                <!-- 5) 画面の説明 -->
                <div class="border-l-4 border-blue-500 pl-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-3">
                        5) 画面の説明は以下の通りです
                    </h2>

                    <div class="bg-gray-50 p-6 rounded-lg border-2 border-gray-200">
                        <div class="relative">
                            <img
                                src="/images/Explanation.png"
                                alt="画面説明"
                                class="w-full h-auto rounded-lg shadow-md"
                            />

                            <!-- 吹き出し1: 残り時間が0になると -->
                            <div
                                class="explanation-bubble"
                                style="top: 2%; left: 5%; max-width: 220px"
                            >
                                <div
                                    class="bg-yellow-100 border-2 border-yellow-400 rounded-lg p-3 shadow-lg"
                                >
                                    <p class="text-sm font-semibold text-gray-800">
                                        残り時間が0になると、その部の試験を終了し、制限時間が残っていても、各部の試験を終了します。
                                    </p>
                                </div>
                            </div>

                            <!-- 吹き出し2: 全ての問題を解き終わったら -->
                            <div
                                class="explanation-bubble"
                                style="top: 2%; right: 5%; max-width: 220px"
                            >
                                <div
                                    class="bg-green-100 border-2 border-green-400 rounded-lg p-3 shadow-lg"
                                >
                                    <p class="text-sm font-semibold text-gray-800">
                                        全ての問題を解き終わったら、このボタンを押してください。
                                    </p>
                                </div>
                            </div>

                            <!-- 吹き出し3: 正しいと思う解答をクリック -->
                            <div
                                class="explanation-bubble"
                                style="top: 35%; right: 5%; max-width: 220px"
                            >
                                <div
                                    class="bg-blue-100 border-2 border-blue-400 rounded-lg p-3 shadow-lg"
                                >
                                    <p class="text-sm font-semibold text-gray-800">
                                        正しいと思う解答をクリックしてください。選択した状態でもう一度クリックすると取り消されます。
                                    </p>
                                </div>
                            </div>

                            <!-- 吹き出し4: チェックをつけておくと（回答状況の右側） -->
                            <div
                                class="explanation-bubble"
                                style="bottom: 30%; left: 25%; max-width: 200px"
                            >
                                <div
                                    class="bg-purple-100 border-2 border-purple-400 rounded-lg p-3 shadow-lg"
                                >
                                    <p class="text-sm font-semibold text-gray-800">
                                        ✓をつけておくと、下の解答状況に✓が表示されます
                                    </p>
                                </div>
                            </div>

                            <!-- 吹き出し5: 問題の解答状況が表示 -->
                            <div
                                class="explanation-bubble"
                                style="bottom: 5%; left: 5%; max-width: 240px"
                            >
                                <div
                                    class="bg-pink-100 border-2 border-pink-400 rounded-lg p-3 shadow-lg"
                                >
                                    <p class="text-sm font-semibold text-gray-800">
                                        問題の解答状況が表示されます。問題をダブルクリックすると、その問題にジャンプします
                                    </p>
                                </div>
                            </div>

                            <!-- 吹き出し6: 表示する問題を切り替え（解答状況の左上） -->
                            <div
                                class="explanation-bubble"
                                style="bottom: 40%; left: 5%; max-width: 200px"
                            >
                                <div
                                    class="bg-orange-100 border-2 border-orange-400 rounded-lg p-3 shadow-lg"
                                >
                                    <p class="text-sm font-semibold text-gray-800">
                                        表示する問題を切り替えます
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6) 開始前の確認 -->
                <div class="border-l-4 border-green-500 pl-4 bg-green-50 p-4 rounded">
                    <h2 class="text-xl font-semibold text-green-800 mb-2">
                        6)
                        試験を受ける準備ができたら、下のボタンをクリックして、適性検査を始めてください。
                    </h2>
                </div>
            </div>

            <!-- 開始ボタン -->
            <div class="text-center">
                <Link
                    :href="isGuest ? route('guest.practice.index') : route('practice.index')"
                    class="inline-block px-8 py-4 rounded-lg text-white text-xl font-bold transition-all bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl"
                >
                    第1部の練習を始める
                </Link>
            </div>

            <!-- 戻るリンク -->
            <div class="text-center mt-4">
                <Link
                    :href="isGuest ? route('guest.test.start') : route('test.start')"
                    class="text-gray-600 hover:text-gray-800 underline"
                >
                    前の画面に戻る
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    isGuest: {
        type: Boolean,
        default: false,
    },
});
</script>

<style scoped>
.explanation-bubble {
    position: absolute;
    z-index: 10;
}

.explanation-bubble:hover {
    z-index: 20;
    transform: scale(1.05);
    transition: transform 0.2s ease-in-out;
}
</style>
