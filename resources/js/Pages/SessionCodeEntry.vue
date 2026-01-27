<script setup lang="ts">
import { Head, useForm, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";

const form = useForm({
    session_code: "",
});

const errorMessage = ref("");

const submit = () => {
    errorMessage.value = "";
    form.post(route("session.verify"), {
        onError: errors => {
            if (errors.session_code) {
                errorMessage.value = errors.session_code;
            } else {
                errorMessage.value = "セッションコードの検証に失敗しました";
            }
        },
        onFinish: () => {
            // 419エラー（CSRF token mismatch）の場合、ページをリロード
            if (form.recentlySuccessful === false && !errorMessage.value) {
                // エラーメッセージがなく失敗している場合は419エラーの可能性
                window.location.reload();
            }
        },
    });
};
</script>

<template>
    <Head title="セッションコード入力" />
    <div class="min-h-screen bg-gray-100 flex flex-col">
        <!-- ヘッダー -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <img src="/images/YIC_logo.png" alt="YIC Logo" class="h-12" />
            </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="flex-1 flex items-center justify-center px-4 py-12">
            <div class="max-w-md w-full space-y-8">
                <!-- タイトル -->
                <div class="text-center">
                    <h1
                        class="text-5xl font-bold text-gray-900 mb-4"
                        style="
                            font-family:
                                &quot;Yu Mincho Demibold&quot;, &quot;游明朝 Demibold&quot;,
                                &quot;YuMincho&quot;, serif;
                        "
                    >
                        プログラマー適性検査
                    </h1>
                    <p class="text-xl text-gray-600 mt-6">セッションコードを入力してください</p>
                </div>

                <!-- セッションコード入力フォーム -->
                <div class="bg-white rounded-lg shadow-md p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label
                                for="session_code"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                セッションコード
                            </label>
                            <input
                                id="session_code"
                                v-model="form.session_code"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg text-center tracking-wider uppercase"
                                placeholder="XXXX-XXXX-XXXX"
                                maxlength="14"
                            />
                            <p v-if="errorMessage" class="mt-2 text-sm text-red-600">
                                {{ errorMessage }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!form.processing">確認</span>
                            <span v-else>確認中...</span>
                        </button>
                    </form>
                </div>

                <!-- 管理者ログインリンク -->
                <div class="text-center mt-8">
                    <Link
                        :href="route('admin.login')"
                        class="text-lg text-gray-600 hover:text-gray-900 underline font-medium"
                    >
                        管理者としてログイン
                    </Link>
                </div>
            </div>
        </div>

        <!-- フッター -->
        <footer class="bg-gray-800 text-white text-center py-4 mt-auto">
            © 2025 YIC Group. All rights reserved.
        </footer>
    </div>
</template>

<style scoped>
input[type="text"]:focus {
    outline: none;
}
</style>
