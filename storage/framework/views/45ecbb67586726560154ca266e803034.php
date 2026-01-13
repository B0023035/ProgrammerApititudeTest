<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">統計・グラフ</h1>
        <p class="text-gray-600">全体の統計情報とグラフ表示</p>
    </div>

    <!-- ナビゲーションタブ -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="<?php echo e(route('results.index')); ?>" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-blue-600 hover:border-gray-300 border-b-2 border-transparent">
                    全セッション一覧
                </a>
                <a href="<?php echo e(route('results.grade-list')); ?>" class="px-6 py-4 text-sm font-medium text-gray-600 hover:text-blue-600 hover:border-gray-300 border-b-2 border-transparent">
                    学年別一覧
                </a>
                <a href="<?php echo e(route('results.statistics')); ?>" class="px-6 py-4 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                    統計・グラフ
                </a>
            </nav>
        </div>
    </div>

    <!-- 日別受験者数 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">日別受験者数（直近30日）</h2>
        <canvas id="dailyChart" height="80"></canvas>
    </div>

    <!-- スコア分布 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">スコア分布</h2>
        <canvas id="scoreDistributionChart" height="80"></canvas>
    </div>

    <!-- パート別平均正答率 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">パート別平均正答率</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <?php $__currentLoopData = $partAverages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part => $average): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="text-center">
                <div class="text-lg font-semibold text-gray-800 mb-2">Part <?php echo e($part); ?></div>
                <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo e($average); ?>%</div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: <?php echo e($average); ?>%"></div>
                </div>
                <div class="text-sm text-gray-600 mt-2">
                    <?php if($part == 1): ?> 言語能力 (40問)
                    <?php elseif($part == 2): ?> 図形認識 (30問)
                    <?php else: ?> 計算能力 (25問)
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <canvas id="partChart" height="60"></canvas>
    </div>

    <!-- 時間帯別受験数 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">時間帯別受験数</h2>
        <canvas id="hourlyChart" height="80"></canvas>
    </div>

    <!-- 詳細統計テーブル -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- パート別詳細 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">パート別詳細統計</h3>
            </div>
            <div class="p-6">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 text-sm font-medium text-gray-600">パート</th>
                            <th class="text-right py-2 text-sm font-medium text-gray-600">平均正答率</th>
                            <th class="text-right py-2 text-sm font-medium text-gray-600">問題数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $partAverages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part => $average): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-3 text-sm font-medium text-gray-800">
                                Part <?php echo e($part); ?>

                                <?php if($part == 1): ?> <span class="text-xs text-gray-500">(言語能力)</span>
                                <?php elseif($part == 2): ?> <span class="text-xs text-gray-500">(図形認識)</span>
                                <?php else: ?> <span class="text-xs text-gray-500">(計算能力)</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-right">
                                <span class="text-lg font-bold text-blue-600"><?php echo e($average); ?>%</span>
                            </td>
                            <td class="py-3 text-right text-sm text-gray-600">
                                <?php if($part == 1): ?> 40問
                                <?php elseif($part == 2): ?> 30問
                                <?php else: ?> 25問
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- スコア統計 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">スコア統計</h3>
            </div>
            <div class="p-6 space-y-4">
                <?php
                    $ranges = [
                        ['label' => '90-100%', 'min' => 85, 'max' => 95, 'color' => 'green'],
                        ['label' => '80-89%', 'min' => 76, 'max' => 84, 'color' => 'blue'],
                        ['label' => '70-79%', 'min' => 67, 'max' => 75, 'color' => 'yellow'],
                        ['label' => '60-69%', 'min' => 57, 'max' => 66, 'color' => 'orange'],
                        ['label' => '0-59%', 'min' => 0, 'max' => 56, 'color' => 'red'],
                    ];
                    $total = array_sum($scoreDistribution);
                ?>

                <?php $__currentLoopData = $ranges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $range): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $count = 0;
                        for($i = 0; $i < 10; $i++) {
                            $rangeMin = $i * 10;
                            $rangeMax = ($i + 1) * 10;
                            if($rangeMin >= ($range['min'] * 100 / 95) && $rangeMax <= ($range['max'] * 100 / 95)) {
                                $count += $scoreDistribution[$i] ?? 0;
                            }
                        }
                        $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                    ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700"><?php echo e($range['label']); ?></span>
                            <span class="text-sm font-bold text-gray-800"><?php echo e($count); ?>人 (<?php echo e($percentage); ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-<?php echo e($range['color']); ?>-500 h-3 rounded-full" style="width: <?php echo e($percentage); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 日別受験者数グラフ
    const dailyData = <?php echo json_encode($dailyStats, 15, 512) ?>;
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date).reverse(),
            datasets: [{
                label: '受験者数',
                data: dailyData.map(d => d.count).reverse(),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
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

    // スコア分布グラフ
    const scoreDistribution = <?php echo json_encode($scoreDistribution, 15, 512) ?>;
    const scoreCtx = document.getElementById('scoreDistributionChart').getContext('2d');
    new Chart(scoreCtx, {
        type: 'bar',
        data: {
            labels: ['0-9%', '10-19%', '20-29%', '30-39%', '40-49%', '50-59%', '60-69%', '70-79%', '80-89%', '90-100%'],
            datasets: [{
                label: '人数',
                data: scoreDistribution,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.5)',
                    'rgba(249, 115, 22, 0.5)',
                    'rgba(251, 191, 36, 0.5)',
                    'rgba(234, 179, 8, 0.5)',
                    'rgba(132, 204, 22, 0.5)',
                    'rgba(34, 197, 94, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(20, 184, 166, 0.5)',
                    'rgba(14, 165, 233, 0.5)',
                    'rgba(99, 102, 241, 0.5)'
                ],
                borderColor: [
                    'rgb(239, 68, 68)',
                    'rgb(249, 115, 22)',
                    'rgb(251, 191, 36)',
                    'rgb(234, 179, 8)',
                    'rgb(132, 204, 22)',
                    'rgb(34, 197, 94)',
                    'rgb(16, 185, 129)',
                    'rgb(20, 184, 166)',
                    'rgb(14, 165, 233)',
                    'rgb(99, 102, 241)'
                ],
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

    // パート別グラフ
    const partAverages = <?php echo json_encode($partAverages, 15, 512) ?>;
    const partCtx = document.getElementById('partChart').getContext('2d');
    new Chart(partCtx, {
        type: 'radar',
        data: {
            labels: ['Part 1\n(言語能力)', 'Part 2\n(図形認識)', 'Part 3\n(計算能力)'],
            datasets: [{
                label: '平均正答率',
                data: [partAverages[1], partAverages[2], partAverages[3]],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgb(59, 130, 246)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // 時間帯別グラフ
    const hourlyData = <?php echo json_encode($hourlyStats, 15, 512) ?>;
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    
    // 0-23時のデータを作成
    const hourlyArray = Array(24).fill(0);
    hourlyData.forEach(item => {
        hourlyArray[item.hour] = item.count;
    });
    
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + '時'),
            datasets: [{
                label: '受験回数',
                data: hourlyArray,
                backgroundColor: 'rgba(147, 51, 234, 0.5)',
                borderColor: 'rgb(147, 51, 234)',
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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/results/statistics.blade.php ENDPATH**/ ?>