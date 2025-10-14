@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">学年別成績一覧</h1>
        <p class="text-gray-600">学年ごとの成績統計</p>
    </div>

    <!-- ナビゲーションタブ -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="{{ route('results.index') }}" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-blue-600 hover:border-gray-300 border-b-2 border-transparent">
                    全セッション一覧
                </a>
                <a href="{{ route('results.grade-list') }}" class="px-6 py-4 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                    学年別一覧
                </a>
                <a href="{{ route('results.statistics') }}" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-blue-600 hover:border-gray-300 border-b-2 border-transparent">
                    統計・グラフ
                </a>
            </nav>
        </div>
    </div>

    <!-- 学年選択 -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('results.grade-list') }}" class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700">学年:</label>
            <select name="grade" onchange="this.form.submit()" 
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="23" {{ $grade == '23' ? 'selected' : '' }}>B0023... (2023年度入学)</option>
                <option value="24" {{ $grade == '24' ? 'selected' : '' }}>B0024... (2024年度入学)</option>
                <option value="25" {{ $grade == '25' ? 'selected' : '' }}>B0025... (2025年度入学)</option>
            </select>
        </form>
    </div>

    <!-- 学年統計 -->
    @if(count($userStats) > 0)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-500 rounded-lg shadow p-6 text-white">
            <div class="text-sm opacity-80 mb-1">総受験者数</div>
            <div class="text-3xl font-bold">{{ count($userStats) }}</div>
        </div>
        <div class="bg-green-500 rounded-lg shadow p-6 text-white">
            <div class="text-sm opacity-80 mb-1">平均スコア</div>
            <div class="text-3xl font-bold">
                {{ round(collect($userStats)->avg('average_score'), 1) }}
                <span class="text-lg">/95</span>
            </div>
        </div>
        <div class="bg-purple-500 rounded-lg shadow p-6 text-white">
            <div class="text-sm opacity-80 mb-1">最高スコア</div>
            <div class="text-3xl font-bold">
                {{ collect($userStats)->max('best_score') }}
                <span class="text-lg">/95</span>
            </div>
        </div>
        <div class="bg-orange-500 rounded-lg shadow p-6 text-white">
            <div class="text-sm opacity-80 mb-1">総受験回数</div>
            <div class="text-3xl font-bold">{{ collect($userStats)->sum('attempts') }}</div>
        </div>
    </div>
    @endif

    <!-- ユーザー一覧テーブル -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">順位</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ユーザー</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">受験回数</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">平均スコア</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最高スコア</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最終受験日</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($userStats as $index => $stat)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($index < 3)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : '' }}
                                {{ $index === 1 ? 'bg-gray-300 text-gray-700' : '' }}
                                {{ $index === 2 ? 'bg-orange-400 text-orange-900' : '' }}
                                font-bold">
                                {{ $index + 1 }}
                            </span>
                        @else
                            <span class="text-gray-600 font-semibold">{{ $index + 1 }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-semibold">{{ substr($stat['user']->name, 0, 1) }}</span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $stat['user']->name }}</div>
                                <div class="text-sm text-gray-500">{{ $stat['user']->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900 font-medium">{{ $stat['attempts'] }}回</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-lg font-bold text-blue-600">{{ $stat['average_score'] }}/95</div>
                        <div class="text-xs text-gray-500">
                            {{ round(($stat['average_score'] / 95) * 100, 1) }}%
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-lg font-bold text-green-600">{{ $stat['best_score'] }}/95</div>
                        <div class="text-xs text-gray-500">
                            {{ round(($stat['best_score'] / 95) * 100, 1) }}%
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($stat['latest_date'])->format('Y-m-d') }}</div>
                        <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($stat['latest_date'])->format('H:i') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('results.user-detail', $stat['user']->id) }}" 
                           class="text-blue-600 hover:text-blue-900">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        この学年の受験データがありません
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(count($userStats) > 0)
    <!-- スコア分布グラフ -->
    <div class="bg-white rounded-lg shadow p-6 mt-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">平均スコア分布</h2>
        <canvas id="distributionChart" height="80"></canvas>
    </div>
    @endif
</div>

@if(count($userStats) > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userStats = @json($userStats);
    
    // スコアを10点刻みで分類
    const distribution = Array(10).fill(0);
    userStats.forEach(stat => {
        const percentage = (stat.average_score / 95) * 100;
        const index = Math.min(9, Math.floor(percentage / 10));
        distribution[index]++;
    });
    
    const ctx = document.getElementById('distributionChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['0-9%', '10-19%', '20-29%', '30-39%', '40-49%', '50-59%', '60-69%', '70-79%', '80-89%', '90-100%'],
            datasets: [{
                label: '人数',
                data: distribution,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endif
@endsection