<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-8">
        <a href="<?php echo e(route('results.index')); ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
            ← 一覧に戻る
        </a>
        <div class="flex items-center">
            <div class="flex-shrink-0 h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-blue-600 font-bold text-2xl"><?php echo e(substr($user->name, 0, 1)); ?></span>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo e($user->name); ?></h1>
                <p class="text-gray-600"><?php echo e($user->email); ?></p>
            </div>
        </div>
    </div>

    <!-- 統計カード -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">総受験回数</div>
            <div class="text-3xl font-bold text-gray-800"><?php echo e(count($sessionDetails)); ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">平均スコア</div>
            <div class="text-3xl font-bold text-blue-600">
                <?php
                    $avgScore = count($sessionDetails) > 0 
                        ? round(collect($sessionDetails)->avg(fn($d) => array_sum(array_column($d['scores'], 'correct'))), 1) 
                        : 0;
                ?>
                <?php echo e($avgScore); ?><span class="text-lg text-gray-500">/95</span>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">最高スコア</div>
            <div class="text-3xl font-bold text-green-600">
                <?php
                    $bestScore = count($sessionDetails) > 0 
                        ? max(array_map(fn($d) => array_sum(array_column($d['scores'], 'correct')), $sessionDetails)) 
                        : 0;
                ?>
                <?php echo e($bestScore); ?><span class="text-lg text-gray-500">/95</span>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600 mb-1">最終受験日</div>
            <div class="text-lg font-bold text-gray-800">
                <?php if(count($sessionDetails) > 0): ?>
                    <?php echo e($sessionDetails[0]['session']->finished_at->format('Y-m-d')); ?>

                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- パート別平均正答率 -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">パート別平均正答率</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php $__currentLoopData = $partAverages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part => $average): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="text-center">
                <div class="text-sm text-gray-600 mb-2">Part <?php echo e($part); ?></div>
                <div class="relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                <?php echo e($average); ?>%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                        <div style="width:<?php echo e($average); ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                    </div>
                </div>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/results/user-detail.blade.php ENDPATH**/ ?>