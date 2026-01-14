<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
// import AdminResultsTabs from "@/Components/AdminResultsTabs.vue";

interface User {
    id: number;
    name: string;
    email: string;
    graduation_year: string;
    exam_sessions: any[];
}

interface Props {
    usersByGrade: { [key: string]: User[] };
}

const props = defineProps<Props>();

const selectedGrade = ref<string | null>(null);
const grades = Object.keys(props.usersByGrade).sort();

const selectGrade = (grade: string | number) => {
    const gradeStr = String(grade);
    selectedGrade.value = selectedGrade.value === gradeStr ? null : gradeStr;
};

const isSelected = (grade: string | number) => {
    return selectedGrade.value === String(grade);
};

const getAverageScore = (user: User) => {
    if (user.exam_sessions.length === 0) return 0;
    const total = user.exam_sessions.reduce(
        (sum: number, session: any) => sum + (session.total_score || 0),
        0
    );
    return Math.round((total / user.exam_sessions.length) * 100) / 100;
};

const getBestScore = (user: User) => {
    if (user.exam_sessions.length === 0) return 0;
    return Math.max(...user.exam_sessions.map((session: any) => session.total_score || 0));
};
</script>

<template>
    <AdminLayout>
        <Head title="卒業年度別一覧" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- ヘッダー -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">🎓 卒業年度別一覧</h1>
                    <p class="mt-2 text-gray-600">卒業年度ごとのユーザーと受験履歴を確認できます</p>
                </div>

                <!-- タブナビゲーション -->
                <AdminResultsTabs />

                <!-- 学年カード -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
                    <div
                        v-for="(users, grade) in usersByGrade"
                        :key="grade"
                        class="bg-white rounded-lg shadow-lg overflow-hidden transition-all hover:shadow-xl"
                    >
                        <div
                            class="p-6 cursor-pointer"
                            :class="isSelected(grade) ? 'bg-blue-50' : 'bg-white'"
                            @click="selectGrade(grade)"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-2xl font-bold text-gray-900">
                                    {{ grade }}
                                </h2>
                                <svg
                                    class="w-6 h-6 text-gray-400 transition-transform"
                                    :class="{
                                        'transform rotate-180': isSelected(grade),
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-100 rounded-lg p-3 text-center">
                                    <p class="text-sm text-gray-600">ユーザー数</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ users.length }}
                                    </p>
                                </div>
                                <div class="bg-green-100 rounded-lg p-3 text-center">
                                    <p class="text-sm text-gray-600">受験回数</p>
                                    <p class="text-2xl font-bold text-green-600">
                                        {{
                                            users.reduce(
                                                (sum, u) => sum + u.exam_sessions.length,
                                                0
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 展開コンテンツ -->
                        <div v-if="isSelected(grade)" class="border-t border-gray-200 bg-gray-50">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    ユーザー一覧
                                </h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="user in users"
                                        :key="user.id"
                                        class="bg-white rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <Link
                                                    :href="
                                                        route('admin.results.user-detail', {
                                                            userId: user.id,
                                                        })
                                                    "
                                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                                >
                                                    {{ user.name }}
                                                </Link>
                                                <p class="text-sm text-gray-500">
                                                    {{ user.email }}
                                                </p>
                                                <div class="grid grid-cols-3 gap-2 mt-3">
                                                    <div
                                                        class="text-center bg-gray-100 rounded p-2"
                                                    >
                                                        <p class="text-xs text-gray-600">
                                                            受験回数
                                                        </p>
                                                        <p class="text-sm font-semibold">
                                                            {{ user.exam_sessions.length }}
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="text-center bg-gray-100 rounded p-2"
                                                    >
                                                        <p class="text-xs text-gray-600">平均点</p>
                                                        <p class="text-sm font-semibold">
                                                            {{ getAverageScore(user) }}
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="text-center bg-gray-100 rounded p-2"
                                                    >
                                                        <p class="text-xs text-gray-600">最高点</p>
                                                        <p class="text-sm font-semibold">
                                                            {{ getBestScore(user) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- データがない場合 -->
                <div
                    v-if="grades.length === 0"
                    class="text-center py-12 bg-white rounded-lg shadow"
                >
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
                    <h3 class="mt-2 text-sm font-medium text-gray-900">データがありません</h3>
                    <p class="mt-1 text-sm text-gray-500">ユーザーデータが登録されていません。</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
