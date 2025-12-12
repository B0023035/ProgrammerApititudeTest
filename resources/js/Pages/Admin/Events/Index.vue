<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface Event {
    id: number;
    name: string;
    passphrase: string;
    begin: string;
    end: string;
    exam_type: string;
    part1_questions?: number;
    part1_time?: number;
    part2_questions?: number;
    part2_time?: number;
    part3_questions?: number;
    part3_time?: number;
    status: string;
    status_color: string;
    created_at: string;
}

const props = defineProps<{
    events: Event[];
}>();

// ソート関連の状態
type SortKey = "name" | "passphrase" | "begin" | "end" | "exam_type" | "status";
type SortOrder = "asc" | "desc";

const sortKey = ref<SortKey>("begin");
const sortOrder = ref<SortOrder>("desc");

// ソート済みのイベントリスト
const sortedEvents = computed(() => {
    const events = [...props.events];

    return events.sort((a, b) => {
        let aVal: any = a[sortKey.value];
        let bVal: any = b[sortKey.value];

        // 日付の場合はDate型に変換
        if (sortKey.value === "begin" || sortKey.value === "end") {
            aVal = new Date(aVal).getTime();
            bVal = new Date(bVal).getTime();
        }

        // 文字列の場合は小文字に変換して比較
        if (typeof aVal === "string") {
            aVal = aVal.toLowerCase();
            bVal = bVal.toLowerCase();
        }

        let comparison = 0;
        if (aVal > bVal) {
            comparison = 1;
        } else if (aVal < bVal) {
            comparison = -1;
        }

        return sortOrder.value === "asc" ? comparison : -comparison;
    });
});

// ソート処理
const sort = (key: SortKey) => {
    if (sortKey.value === key) {
        // 同じキーの場合は順序を反転
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        // 異なるキーの場合は新しいキーで昇順
        sortKey.value = key;
        sortOrder.value = "asc";
    }
};

const getExamTypeLabel = (event: Event) => {
    const labels: { [key: string]: string } = {
        "30min": "30分版",
        "45min": "45分版",
        full: "通常版",
        custom: "カスタム",
    };
    return labels[event.exam_type] || event.exam_type;
};

const getExamTypeDetails = (event: Event) => {
    if (event.exam_type === "custom") {
        const p1 = event.part1_questions || 0;
        const p2 = event.part2_questions || 0;
        const p3 = event.part3_questions || 0;
        const total = p1 + p2 + p3;

        const t1 = event.part1_time ? Math.round(event.part1_time / 60) : 0;
        const t2 = event.part2_time ? Math.round(event.part2_time / 60) : 0;
        const t3 = event.part3_time ? Math.round(event.part3_time / 60) : 0;
        const totalTime = t1 + t2 + t3;

        const timeLabel = totalTime === 0 ? "無制限" : `${totalTime}分`;

        return [
            `P1: ${p1}問 (${t1 === 0 ? "無制限" : t1 + "分"})`,
            `P2: ${p2}問 (${t2 === 0 ? "無制限" : t2 + "分"})`,
            `P3: ${p3}問 (${t3 === 0 ? "無制限" : t3 + "分"})`,
            `合計: ${total}問 (${timeLabel})`,
        ];
    }
    return [];
};

const terminateEvent = (id: number) => {
    if (confirm("このイベントを早期終了しますか?")) {
        router.post(route("admin.events.terminate", { event: id }));
    }
};

const deleteEvent = (id: number) => {
    if (confirm("このイベントを削除しますか?この操作は取り消せません。")) {
        router.delete(route("admin.events.destroy", { event: id }));
    }
};

// ソートアイコンを表示するヘルパー
const getSortIcon = (key: SortKey) => {
    if (sortKey.value !== key) {
        return "⇅";
    }
    return sortOrder.value === "asc" ? "↑" : "↓";
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
                        <h1 class="text-3xl font-bold text-gray-900">🎫 イベント管理</h1>
                        <p class="mt-2 text-gray-600">セッションコードとイベント期間を管理</p>
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
                    <div v-if="events.length === 0" class="text-center py-12 text-gray-500">
                        <p class="text-lg">イベントがありません</p>
                        <p class="text-sm mt-2">新規イベントを作成してください</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        @click="sort('name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>イベント名</span>
                                            <span class="text-gray-400">{{
                                                getSortIcon("name")
                                            }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('passphrase')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>セッションコード</span>
                                            <span class="text-gray-400">{{
                                                getSortIcon("passphrase")
                                            }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('begin')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>期間</span>
                                            <span class="text-gray-400">{{
                                                getSortIcon("begin")
                                            }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('exam_type')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>出題形式</span>
                                            <span class="text-gray-400">{{
                                                getSortIcon("exam_type")
                                            }}</span>
                                        </div>
                                    </th>
                                    <th
                                        @click="sort('status')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100 select-none"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>ステータス</span>
                                            <span class="text-gray-400">{{
                                                getSortIcon("status")
                                            }}</span>
                                        </div>
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
                                    v-for="event in sortedEvents"
                                    :key="event.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ event.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-mono">
                                        <div
                                            class="text-sm text-gray-900 bg-gray-100 px-3 py-1 rounded inline-block"
                                        >
                                            {{ event.passphrase }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            開始:
                                            {{ new Date(event.begin).toLocaleString("ja-JP") }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            終了:
                                            {{ new Date(event.end).toLocaleString("ja-JP") }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col space-y-1">
                                            <span
                                                class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs font-semibold inline-block w-fit"
                                            >
                                                {{ getExamTypeLabel(event) }}
                                            </span>
                                            <template v-if="event.exam_type === 'custom'">
                                                <div
                                                    v-for="(detail, index) in getExamTypeDetails(
                                                        event
                                                    )"
                                                    :key="index"
                                                    class="text-xs text-gray-600"
                                                >
                                                    {{ detail }}
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="{
                                                'bg-blue-100 text-blue-800':
                                                    event.status_color === 'blue',
                                                'bg-green-100 text-green-800':
                                                    event.status_color === 'green',
                                                'bg-gray-100 text-gray-800':
                                                    event.status_color === 'gray',
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
                                            v-if="event.status_color === 'green'"
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
