<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Event {
    id: number;
    name: string;
    passphrase: string;
    begin: string;
    end: string;
    exam_type: string;
    status: string;
    status_color: string;
    created_at: string;
}

const props = defineProps<{
    events: Event[];
}>();

const getExamTypeLabel = (type: string) => {
    const labels: { [key: string]: string } = {
        "30min": "30分版",
        "45min": "45分版",
        full: "通常版",
    };
    return labels[type] || type;
};

const terminateEvent = (id: number) => {
    if (confirm("このイベントを早期終了しますか？")) {
        router.post(route("admin.events.terminate", { event: id }));
    }
};

const deleteEvent = (id: number) => {
    if (confirm("このイベントを削除しますか？この操作は取り消せません。")) {
        router.delete(route("admin.events.destroy", { event: id }));
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="イベント管理" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            🎫 イベント管理
                        </h1>
                        <p class="mt-2 text-gray-600">
                            セッションコードとイベント期間を管理
                        </p>
                    </div>
                    <Link
                        :href="route('admin.events.create')"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                    >
                        + 新規イベント作成
                    </Link>
                </div>

                <!-- イベント一覧 -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div
                        v-if="events.length === 0"
                        class="text-center py-12 text-gray-500"
                    >
                        <p class="text-lg">イベントがありません</p>
                        <p class="text-sm mt-2">
                            新規イベントを作成してください
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        イベント名
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        セッションコード
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        期間
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        出題形式
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        ステータス
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                    >
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="event in events"
                                    :key="event.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ event.name }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap font-mono"
                                    >
                                        <div
                                            class="text-sm text-gray-900 bg-gray-100 px-3 py-1 rounded inline-block"
                                        >
                                            {{ event.passphrase }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            開始:
                                            {{
                                                new Date(
                                                    event.begin
                                                ).toLocaleString("ja-JP")
                                            }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            終了:
                                            {{
                                                new Date(
                                                    event.end
                                                ).toLocaleString("ja-JP")
                                            }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-semibold"
                                        >
                                            {{
                                                getExamTypeLabel(
                                                    event.exam_type
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-blue-100 text-blue-800':
                                                    event.status_color ===
                                                    'blue',
                                                'bg-green-100 text-green-800':
                                                    event.status_color ===
                                                    'green',
                                                'bg-gray-100 text-gray-800':
                                                    event.status_color ===
                                                    'gray',
                                            }"
                                        >
                                            {{ event.status }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2"
                                    >
                                        <Link
                                            :href="
                                                route('admin.events.edit', {
                                                    event: event.id,
                                                })
                                            "
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            編集
                                        </Link>
                                        <button
                                            v-if="
                                                event.status_color === 'green'
                                            "
                                            @click="terminateEvent(event.id)"
                                            class="text-orange-600 hover:text-orange-900"
                                        >
                                            早期終了
                                        </button>
                                        <button
                                            @click="deleteEvent(event.id)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
