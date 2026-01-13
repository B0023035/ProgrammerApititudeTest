<script setup lang="ts">
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const form = useForm({
    school_name: "",
    guest_name: "",
});

const isSubmitting = ref(false);

const handleSubmit = () => {
    if (!form.school_name.trim() || !form.guest_name.trim()) {
        alert("所属と名前を入力してください");
        return;
    }

    isSubmitting.value = true;

    form.post(route("guest.info.store"), {
        onSuccess: () => {
            // 登録完了
        },
        onError: errors => {
            alert("登録に失敗しました。もう一度お試しください。");
            isSubmitting.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <Head title="ゲスト情報入力" />
    <div class="min-h-screen bg-gray-100 flex flex-col">
        <!-- ロゴ表示 -->
        <div class="bg-gray-100 pt-8 px-4">
            <img src="/images/YIC_logo.png" alt="YIC Logo" class="YIC_logo" />
        </div>

        <!-- メインコンテンツ -->
        <div class="flex-1 flex flex-col items-center justify-center px-4">
            <!-- タイトル -->
            <h1 class="custom-title" style="margin-bottom: 120px">プログラマー適性検査</h1>

            <!-- 入力フォーム -->
            <div class="w-full max-w-xl">
                <!-- 所属入力 -->
                <div class="flex items-center" style="margin-bottom: 40px">
                    <label for="school_name" class="form-label w-24 text-right mr-6"> 所属: </label>
                    <input
                        id="school_name"
                        v-model="form.school_name"
                        type="text"
                        class="form-input flex-1"
                        placeholder="YIC高等学校"
                        maxlength="100"
                        :disabled="isSubmitting"
                        @keydown.enter="handleSubmit"
                    />
                </div>

                <!-- 名前入力 -->
                <div class="flex items-center" style="margin-bottom: 40px">
                    <label for="guest_name" class="form-label w-24 text-right mr-6"> 名前: </label>
                    <input
                        id="guest_name"
                        v-model="form.guest_name"
                        type="text"
                        class="form-input flex-1"
                        placeholder="山口 晋作"
                        maxlength="100"
                        :disabled="isSubmitting"
                        @keydown.enter="handleSubmit"
                    />
                </div>

                <!-- 始めるボタン -->
                <div class="flex justify-center" style="margin-top: 80px">
                    <button
                        @click="handleSubmit"
                        :disabled="
                            isSubmitting || !form.school_name.trim() || !form.guest_name.trim()
                        "
                        class="start-button"
                        :class="{
                            'opacity-50 cursor-not-allowed':
                                isSubmitting || !form.school_name.trim() || !form.guest_name.trim(),
                        }"
                    >
                        {{ isSubmitting ? "処理中..." : "始める" }}
                    </button>
                </div>

                <!-- 注意書き -->
                <div class="text-center mt-8">
                    <p class="notice-text">指示があるまでボタンを押さないでください</p>
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
.YIC_logo {
    width: 403px;
    height: 51px;
    max-width: 100%;
}

.custom-title {
    font-family: "Yu Mincho Demibold", "游明朝 Demibold", "YuMincho", serif;
    font-size: 64px;
    text-align: center;
    color: #333;
    letter-spacing: 0.05em;
}

.form-label {
    font-family: "MS PGothic", "ＭＳ Ｐゴシック", sans-serif;
    font-size: 16px;
    font-weight: normal;
    color: #333;
}

.form-input {
    padding: 10px 14px;
    font-size: 16px;
    font-family: "MS PGothic", "ＭＳ Ｐゴシック", sans-serif;
    border: 2px solid #4a5568;
    border-radius: 4px;
    background-color: white;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input:disabled {
    background-color: #f3f4f6;
    cursor: not-allowed;
}

.start-button {
    padding: 14px 70px;
    font-size: 20px;
    font-family: "MS PGothic", "ＭＳ Ｐゴシック", sans-serif;
    background-color: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s;
    font-weight: normal;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.start-button:hover:not(:disabled) {
    background-color: #2563eb;
}

.notice-text {
    font-family: "MS PGothic", "ＭＳ Ｐゴシック", sans-serif;
    font-size: 13px;
    color: #666;
}

@media (max-width: 768px) {
    .custom-title {
        font-size: 40px;
    }

    .form-label {
        font-size: 14px;
        width: 70px;
        margin-right: 1rem;
    }

    .form-input {
        font-size: 14px;
        padding: 8px 12px;
    }

    .start-button {
        padding: 12px 50px;
        font-size: 18px;
    }

    .YIC_logo {
        width: 300px;
        height: 38px;
    }
}
</style>
