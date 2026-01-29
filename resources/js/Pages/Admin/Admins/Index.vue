<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

interface Admin {
    id: number;
    name: string;
    email: string;
    created_at: string;
}

interface Props {
    admins: {
        data: Admin[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    sort: string;
    direction: string;
}

const props = defineProps<Props>();

const searchQuery = ref("");

const filteredAdmins = computed(() => {
    if (!searchQuery.value) {
        return props.admins.data;
    }

    const query = searchQuery.value.toLowerCase();
    return props.admins.data.filter(
        admin =>
            admin.name.toLowerCase().includes(query) || admin.email.toLowerCase().includes(query)
    );
});

// ソート機能
const sortBy = (field: string) => {
    let direction = "asc";
    if (props.sort === field) {
        direction = props.direction === "asc" ? "desc" : "asc";
    }

    router.get(
        route("admin.admins.index"),
        {
            sort: field,
            direction: direction,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// ソートアイコンを取得
const getSortIcon = (field: string) => {
    if (props.sort !== field) {
        return "↕";
    }
    return props.direction === "asc" ? "↑" : "↓";
};

// ソートヘッダーのクラス
const getSortClass = (field: string) => {
    return props.sort === field ? "bg-blue-100 text-blue-700" : "";
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString("ja-JP");
};

const deleteAdmin = (admin: Admin) => {
    if (confirm(`管理者「${admin.name}」を削除しますか？`)) {
        router.delete(route("admin.admins.destroy", admin.id));
    }
};
</script>

<template>
    <Head title="管理者アカウント管理" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">管理者アカウント管理</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- 統計カード -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div
                        class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-1">総管理者数</p>
                                <p class="text-4xl font-bold">
                                    {{ admins.total }}
                                </p>
                            </div>
                            <svg
                                class="w-12 h-12 opacity-80"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div
                        class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm opacity-90 mb-1">ページ</p>
                                <p class="text-4xl font-bold">
                                    {{ admins.current_page }} /
                                    {{ admins.last_page }}
                                </p>
                            </div>
                            <svg
                                class="w-12 h-12 opacity-80"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- 新規作成ボタン -->
                    <Link
                        :href="route('admin.admins.create')"
                        class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white hover:from-green-600 hover:to-green-700 transition-all flex items-center justify-between"
                    >
                        <div>
                            <p class="text-sm opacity-90 mb-1">管理者を追加</p>
                            <p class="text-2xl font-bold">＋ 新規作成</p>
                        </div>
                        <svg
                            class="w-12 h-12 opacity-80"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                            />
                        </svg>
                    </Link>
                </div>

                <!-- 検索フィルター -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label
                                for="search"
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                検索
                            </label>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="ユーザー名、メールアドレスで検索..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div class="pt-7">
                            <button
                                @click="searchQuery = ''"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                            >
                                クリア
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 管理者一覧テーブル -->
                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        @click="sortBy('id')"
                                        :class="getSortClass('id')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                    >
                                        ID <span class="ml-1">{{ getSortIcon("id") }}</span>
                                    </th>
                                    <th
                                        @click="sortBy('name')"
                                        :class="getSortClass('name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                    >
                                        ユーザー名
                                        <span class="ml-1">{{ getSortIcon("name") }}</span>
                                    </th>
                                    <th
                                        @click="sortBy('email')"
                                        :class="getSortClass('email')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                    >
                                        メールアドレス
                                        <span class="ml-1">{{ getSortIcon("email") }}</span>
                                    </th>
                                    <th
                                        @click="sortBy('created_at')"
                                        :class="getSortClass('created_at')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors select-none"
                                    >
                                        登録日
                                        <span class="ml-1">{{ getSortIcon("created_at") }}</span>
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="admin in filteredAdmins"
                                    :key="admin.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        #{{ admin.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div
                                                class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold"
                                            >
                                                {{ admin.name.charAt(0) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ admin.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ admin.email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(admin.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            @click="deleteAdmin(admin)"
                                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="filteredAdmins.length === 0" class="text-center py-12">
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                管理者が見つかりません
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                検索条件に一致する管理者がいません。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
