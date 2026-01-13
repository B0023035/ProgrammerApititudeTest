<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <!-- ヘッダー -->
    <div class="mb-8">
        <a href="<?php echo e(route('results.index')); ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
            ← 一覧に戻る
        </a>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">セッション詳細</h1>
                    <div class="flex items-center space-x-4 text-sm text-gray-600">
                        <span>ユーザー: <a href="<?php echo e(route('results.user-detail', $session->user_id)); ?>" class="text-blue-600 hover:underline"><?php echo e($session->user->name); ?></a></span>
                        <span>受験日時: <?php echo e($session->finished_at->format('Y-m-d H:i')); ?></span>
                        <span>所要時間: <?php echo e($session->started_at->diffInMinutes($session->finished_at)); ?>分</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-blue-600">
                        <?php echo e(array_sum(array_column($partScores, 'correct'))); ?>/95
                    </div>
                    <div class="text-sm text-gray-500">
                        <?php echo e(round((array_sum(array_column($partScores, 'correct')) / 95) * 100, 1)); ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- パート別サマリー -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php $__currentLoopData = $partScores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part => $score): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-lg font-semibold text-gray-800 mb-4">Part <?php echo e($part); ?></div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl font-bold text-gray-800"><?php echo e($score['correct']); ?>/<?php echo e($score['total']); ?></span>
                <span class="text-lg font-semibold text-blue-600"><?php echo e($score['percentage']); ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: <?php echo e($score['percentage']); ?>%"></div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <!-- 各パートの詳細 -->
    <?php $__currentLoopData = $answersByPart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $part => $answers): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="bg-white rounded-lg shadow mb-6 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">Part <?php echo e($part); ?> の解答</h2>
                <span class="text-sm text-gray-600">
                    <?php echo e($partScores[$part]['correct']); ?>/<?php echo e($partScores[$part]['total']); ?> 正解
                </span>
            </div>
        </div>
        
        <div class="divide-y divide-gray-200">
            <?php $__currentLoopData = $answers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $answer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="p-6 <?php echo e($answer->is_correct ? 'bg-green-50' : 'bg-red-50'); ?>">
                <div class="flex items-start">
                    <!-- 問題番号と正誤マーク -->
                    <div class="flex-shrink-0 mr-4">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center <?php echo e($answer->is_correct ? 'bg-green-500' : 'bg-red-500'); ?> text-white font-bold">
                            <?php echo e($answer->question->number); ?>

                        </div>
                    </div>

                    <!-- 問題内容 -->
                    <div class="flex-1">
                        <div class="mb-4">
                            <?php if($answer->question->image): ?>
                                <img src="<?php echo e(asset('storage/questions/' . $answer->question->image)); ?>" 
                                     alt="Question <?php echo e($answer->question->number); ?>" 
                                     class="max-w-md rounded-lg shadow">
                            <?php endif; ?>
                            <?php if($answer->question->text): ?>
                                <p class="text-gray-800 text-lg"><?php echo e($answer->question->text); ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- 選択肢 -->
                        <div class="grid grid-cols-1 gap-2">
                            <?php $__currentLoopData = $answer->question->choices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center p-3 rounded-lg border-2
                                <?php echo e($choice->label === $answer->choice && $answer->is_correct ? 'border-green-500 bg-green-100' : ''); ?>

                                <?php echo e($choice->label === $answer->choice && !$answer->is_correct ? 'border-red-500 bg-red-100' : ''); ?>

                                <?php echo e($choice->is_correct && $choice->label !== $answer->choice ? 'border-blue-500 bg-blue-50' : ''); ?>

                                <?php echo e(!$choice->is_correct && $choice->label !== $answer->choice ? 'border-gray-200 bg-white' : ''); ?>">
                                
                                <span class="font-bold mr-3 text-lg"><?php echo e($choice->label); ?>.</span>
                                
                                <?php if($choice->image): ?>
                                    <img src="<?php echo e(asset('storage/choices/' . $choice->image)); ?>" 
                                         alt="Choice <?php echo e($choice->label); ?>" 
                                         class="max-h-20">
                                <?php else: ?>
                                    <span class="text-gray-800"><?php echo e($choice->text); ?></span>
                                <?php endif; ?>

                                <!-- マーク表示 -->
                                <div class="ml-auto flex items-center space-x-2">
                                    <?php if($choice->label === $answer->choice): ?>
                                        <span class="text-sm font-semibold <?php echo e($answer->is_correct ? 'text-green-700' : 'text-red-700'); ?>">
                                            あなたの回答
                                        </span>
                                    <?php endif; ?>
                                    <?php if($choice->is_correct): ?>
                                        <span class="px-2 py-1 bg-blue-500 text-white text-xs font-bold rounded">
                                            正解
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <!-- 結果表示 -->
                        <div class="mt-4 p-3 rounded-lg <?php echo e($answer->is_correct ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php if($answer->is_correct): ?>
                                <span class="font-semibold">✓ 正解です！</span>
                            <?php else: ?>
                                <span class="font-semibold">✗ 不正解</span>
                                <span class="ml-2">正解は 
                                    <strong><?php echo e($answer->question->choices->where('is_correct', 1)->first()->label); ?></strong> 
                                    です
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/results/session-detail.blade.php ENDPATH**/ ?>