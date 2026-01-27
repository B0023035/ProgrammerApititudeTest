<script setup lang="ts">
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const serverError = ref("");

const submit = () => {
    serverError.value = "";

    // CSRFトークンを更新してからログイン
    const doLogin = () => {
        form.post(route("admin.login.store"), {
            preserveScroll: true,
            onFinish: () => {
                form.reset("password");
            },
            onError: errors => {
                if (errors.email) {
                    serverError.value = errors.email;
                } else if (errors.password) {
                    serverError.value = errors.password;
                } else {
                    serverError.value = "ログインに失敗しました。";
                }
            },
        });
    };

    // CSRFトークンを強制更新
    if (typeof (window as any).forceRefreshCSRF === "function") {
        (window as any)
            .forceRefreshCSRF()
            .then(() => doLogin())
            .catch(() => doLogin());
    } else {
        doLogin();
    }
};
</script>

<template>
    <Head title="管理者ログイン" />

    <div
        class="min-h-screen flex items-center justify-center bg-gradient-to-b from-gray-100 to-gray-200"
    >
        <div class="relative w-full max-w-md">
            <!-- ロゴエリア -->
            <div class="text-center mb-8">
                <div class="inline-block">
                    <img
                        src="/images/YIC_logo.png"
                        alt="YIC Logo"
                        class="h-16 mx-auto mb-4"
                    />
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">管理者ログイン</h1>
                    <p class="text-gray-500 text-sm">Admin Portal</p>
                </div>
            </div>

            <!-- ログインフォーム -->
            <div class="bg-white/95 backdrop-blur-sm shadow-2xl rounded-2xl p-8">
                <!-- ステータスメッセージ -->
                <div v-if="status" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600">{{ status }}</p>
                </div>

                <!-- サーバーエラー表示 -->
                <div v-if="serverError" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600">{{ serverError }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- メールアドレス -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            メールアドレス
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <svg
                                    class="h-5 w-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                                    />
                                </svg>
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="username"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                :class="{ 'border-red-500': form.errors.email }"
                                placeholder="admin@example.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- パスワード -->
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-semibold text-gray-700 mb-2"
                        >
                            パスワード
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <svg
                                    class="h-5 w-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                    />
                                </svg>
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                :class="{
                                    'border-red-500': form.errors.password,
                                }"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- ログイン状態を保持 -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                        />
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            ログイン状態を保持する
                        </label>
                    </div>

                    <!-- ログインボタン -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center items-center px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg"
                    >
                        <svg
                            v-if="form.processing"
                            class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span v-if="form.processing">ログイン中...</span>
                        <span v-else>ログイン</span>
                    </button>
                </form>

                <!-- 戻るリンク -->
                <div class="mt-6 text-center">
                    <a
                        href="/"
                        class="text-sm text-gray-600 hover:text-gray-900 underline transition-colors"
                    >
                        ← トップページに戻る
                    </a>
                </div>
            </div>

            <!-- フッター情報 -->
            <div class="mt-8 text-center text-gray-400 text-sm">
                <p>© 2025 YIC Group. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.relative {
    animation: fadeIn 0.5s ease-out;
}
</style>
