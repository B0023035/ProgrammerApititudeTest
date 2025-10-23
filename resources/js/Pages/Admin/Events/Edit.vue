<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Event {
    id: number;
    name: string;
    passphrase: string;
    begin: string;
    end: string;
    exam_type: string;
}

const props = defineProps<{
    event: Event;
}>();

const form = useForm({
    name: props.event.name,
    passphrase: props.event.passphrase,
    begin: props.event.begin,
    end: props.event.end,
    exam_type: props.event.exam_type,
});

const isGenerating = ref(false);

const generatePassphrase = async () => {
    isGenerating.value = true;
    try {
        const response = await fetch(route("admin.events.generate-passphrase"));
        const data = await response.json();
        form.passphrase = data.passphrase;
    } catch (error) {
        console.error("パスフレーズ生成エラー:", error);
        alert("パスフレーズの生成に失敗しました。");
    } finally {
        isGenerating.value = false;
    }
};

const submit = () => {
    form.put(route("admin.events.update", { event: props.event.id }), {
        onSuccess: () => {
            alert("イベントを更新しました。");
        },
        onError: (errors) => {
            console.error("エラー:", errors);
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="イベント編集" />

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-6">
                    <Link
                        :href="route('admin.events.index')"
                        class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4"
                    >
                        <svg
                            class="w-5 h-5 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        イベント一覧に戻る
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900">
                        イベント編集
                    </h1>
                    <p class="mt-2 text-gray-600">イベント情報を編集します</p>
                </div>

                <!-- フォーム -->
                <div class="bg-white rounded-lg shadow p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- イベント名 -->
                        <div>
                            <label
                                for="name"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                イベント名
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="例: 2025年度 春期 適性検査"
                            />
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- パスフレーズ(セッションコード) -->
                        <div>
                            <label
                                for="passphrase"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                セッションコード
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-2">
                                <input
                                    id="passphrase"
                                    v-model="form.passphrase"
                                    type="text"
                                    required
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase font-mono"
                                    placeholder="例: ABCD-EFGH-IJKL"
                                />
                                <button
                                    type="button"
                                    @click="generatePassphrase"
                                    :disabled="isGenerating"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                                >
                                    <span v-if="!isGenerating"
                                        >ランダム生成</span
                                    >
                                    <span v-else>生成中...</span>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                受験者がアクセスする際に使用するコードです
                            </p>
                            <p
                                v-if="form.errors.passphrase"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.passphrase }}
                            </p>
                        </div>

                        <!-- 開始日時 -->
                        <div>
                            <label
                                for="begin"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                開始日時
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="begin"
                                v-model="form.begin"
                                type="datetime-local"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p
                                v-if="form.errors.begin"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.begin }}
                            </p>
                        </div>

                        <!-- 終了日時 -->
                        <div>
                            <label
                                for="end"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                終了日時
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="end"
                                v-model="form.end"
                                type="datetime-local"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p
                                v-if="form.errors.end"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.end }}
                            </p>
                        </div>

                        <!-- 出題形式 -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                出題形式
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50':
                                            form.exam_type === 'full',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="full"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            フル版(推奨)
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Part1: 40問、Part2: 30問、Part3:
                                            25問(合計95問)
                                        </div>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50':
                                            form.exam_type === '45min',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="45min"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            45分版
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            問題数を調整した45分版(実装予定)
                                        </div>
                                    </div>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{
                                        'border-blue-500 bg-blue-50':
                                            form.exam_type === '30min',
                                    }"
                                >
                                    <input
                                        v-model="form.exam_type"
                                        type="radio"
                                        value="30min"
                                        class="mr-3"
                                    />
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            30分版
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            問題数を調整した30分版(実装予定)
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <p
                                v-if="form.errors.exam_type"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.exam_type }}
                            </p>
                        </div>

                        <!-- ボタン -->
                        <div class="flex justify-end space-x-3 pt-4">
                            <Link
                                :href="route('admin.events.index')"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                キャンセル
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="!form.processing">更新する</span>
                                <span v-else>更新中...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
