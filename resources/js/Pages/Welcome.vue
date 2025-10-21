<script setup lang="ts">
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { onMounted } from "vue";

const page = usePage();

onMounted(() => {
    const currentPath = window.location.pathname;

    // 管理者ページへのアクセスは何もしない
    if (currentPath.startsWith("/admin")) {
        return;
    }

    // 一般ユーザーがログインしている場合、test-startへリダイレクト
    if (page.props.auth?.user) {
        router.visit("/test-start");
    }
});
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen main_contents bg-gray-100">
        <!-- ロゴ表示 -->
        <div class="bg-gray-100">
            <img src="/images/YIC_logo.png" class="YIC_logo" />
        </div>

        <!-- タイトル -->
        <div>
            <h1 class="custom-title bg-gray-100">プログラマー適性検査</h1>
        </div>

        <!-- 未ログイン用リンク -->
        <div
            class="bg-gray-100 flex flex-col sm:justify-center items-center pt-6 sm:pt-0 space-y-2"
        >
            <Link
                href="/login"
                class="underline text-sm text-gray-600 hover:text-gray-900"
            >
                Log in
            </Link>
            <Link
                href="/register"
                class="underline text-sm text-gray-600 hover:text-gray-900"
            >
                Register
            </Link>
            <Link
                href="/guest/info"
                class="underline text-sm text-gray-600 hover:text-gray-900"
            >
                Guest
            </Link>
            <!-- 管理者ログインは通常のaタグを使用 -->
            <a
                href="/admin/login"
                class="underline text-sm text-gray-600 hover:text-gray-900"
            >
                Administrator Log in
            </a>
        </div>
    </div>

    <!-- フッター -->
    <footer class="bg-gray-800 text-white text-center py-4">
        © 2025 YIC Group. All rights reserved.
    </footer>
</template>

<style>
.YIC_logo {
    position: relative;
    top: 0;
    left: 0;
    width: 403px;
    height: 51px;
}
.custom-title {
    padding-top: 100px;
    font-family: "Yu Mincho Demibold", "游明朝 Demibold", "YuMincho", serif;
    font-size: 50px;
    text-align: center;
}
.main_contents {
    height: 100%;
}
</style>
