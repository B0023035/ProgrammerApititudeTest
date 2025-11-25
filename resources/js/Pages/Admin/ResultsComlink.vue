<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

interface GradeData {
    id: number;
    name: string;
    subject: string;
    score: number;
    exam_session_id: number;
}

interface ProcessedGrade extends GradeData {
    grade: string;
}

interface GradeCounts {
    A: number;
    B: number;
    C: number;
    D: number;
    F: number;
}

interface Session {
    id: number;
    user_id: number;
    session_uuid: string;
    total_score: number;
    total_questions: number;
    rank: string;
    finished_at: string;
    event?: {
        id: number;
        name: string;
    };
    user: {
        id: number;
        name: string;
        email: string;
    };
}

const props = defineProps<{
    sessions?: Session[];
    users?: any[];
    events?: string[];
}>();

const loading = ref(false);
const searchQuery = ref("");

const sessionsList = computed(() => {
    return props.sessions ?? [];
});

const filteredSessions = computed(() => {
    if (!searchQuery.value) return sessionsList.value;

    const q = searchQuery.value.toLowerCase();
    return sessionsList.value.filter((s: any) =>
        s.user.name.toLowerCase().includes(q) ||
        s.user.email.toLowerCase().includes(q) ||
        (s.rank && s.rank.toLowerCase().includes(q))
    );
});

const getRankColor = (rank: string) => {
    const colors: { [key: string]: string } = {
        Platinum: "text-purple-600 bg-purple-100",
        Gold: "text-yellow-600 bg-yellow-100",
        Silver: "text-gray-600 bg-gray-100",
        Bronze: "text-orange-600 bg-orange-100",
    };
    return colors[rank] || "text-gray-600 bg-gray-100";
};

const viewSessionDetail = (sessionId: number) => {
    router.visit(route("admin.results.session-detail", { sessionId }));
};

const viewUserDetail = (userId: number) => {
    router.visit(route("admin.results.user-detail", { userId }));
};

onMounted(() => {
    // nothing special for now
});
</script>

<template>
    <AdminLayout>
        <Head title="成績管理 (Comlink)" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">📊 成績管理 (Comlink)</h1>
                    <p class="mt-2 text-gray-600">Web Workerを活用した高速成績分析システム</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">総セッション数</p>
                                <p class="text-3xl font-bold text-gray-900">
                                    {{ sessionsList.length }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">表示中</p>
                                <p class="text-3xl font-bold text-gray-900">
                                    {{ filteredSessions.length }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">（Comlink 表示）</p>
                                <p class="text-3xl font-bold text-gray-900"> </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">検索</label>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="ユーザー名、メールアドレス、ランクで検索..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div class="pt-7">
                            <button @click="searchQuery = ''" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">クリア</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">総合得点</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ランク</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">完了日時</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="session in filteredSessions" :key="session.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-blue-600 cursor-pointer hover:text-blue-800" @click="viewUserDetail(session.user_id)">
                                            {{ session.user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">{{ session.user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-bold text-gray-900">{{ session.total_score }}点</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getRankColor(session.rank)" class="px-3 py-1 rounded-full text-sm font-semibold">{{ session.rank }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ new Date(session.finished_at).toLocaleString('ja-JP') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button @click="viewSessionDetail(session.id)" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">詳細を見る</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="filteredSessions.length === 0" class="text-center py-12 text-gray-500">データがありません</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
