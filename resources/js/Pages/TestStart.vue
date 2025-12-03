<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import ResponsiveUserProfile from "@/Components/ResponsiveUserProfile.vue";
import type { User } from "@/types"; // User 型があれば

// props 取得（型をバイパス）
const page = usePage() as any;

// ゲスト対応なので optional チェーンでアクセス
const authUser: User | null = page.props.auth?.user ?? null;

function handleImageError() {
    document.getElementById("screenshot-container")?.classList.add("!hidden");
    document.getElementById("docs-card")?.classList.add("!row-span-1");
    document.getElementById("docs-card-content")?.classList.add("!flex-row");
    document.getElementById("background")?.classList.add("!hidden");
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Home" />
        <div class="min-h-screen main_contents bg-gray-100">
            <!-- ヘッダー -->
            <div class="flex items-center justify-between bg-gray-100 px-4 py-2">
                <img src="/images/YIC_logo.png" class="YIC_logo" />
                <div v-if="authUser">
                    <ResponsiveUserProfile :user="authUser" />
                </div>
            </div>

            <!-- タイトル -->
            <div class="text-center title-container">
                <h1 class="custom-title">プログラマー適性検査</h1>
            </div>

            <!-- スタートリンク -->
            <div class="flex justify-center button-container">
                <Link v-if="authUser" :href="route('practice.instructions')" class="start-button">
                    始める
                </Link>
                <div v-else class="space-x-4">
                    <Link
                        :href="route('login')"
                        class="underline text-sm text-gray-600 hover:text-gray-900"
                    >
                        ログイン
                    </Link>
                    <Link
                        :href="route('register')"
                        class="underline text-sm text-gray-600 hover:text-gray-900"
                    >
                        登録
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.YIC_logo {
    width: 403px;
    height: 51px;
}

.title-container {
    margin-top: 120px;
}

.custom-title {
    font-family: "Yu Mincho Demibold", "游明朝 Demibold", "YuMincho", serif;
    font-size: 60px;
}

.button-container {
    margin-top: 180px;
}

.start-button {
    display: inline-block;
    width: 280px;
    padding: 20px 40px;
    font-size: 22px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    color: white;
    background-color: #2563eb;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.start-button:hover {
    background-color: #1d4ed8;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.main_contents {
    height: 100%;
}
</style>
