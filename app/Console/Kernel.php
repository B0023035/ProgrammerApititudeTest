<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // セッションクリーンアップを1時間ごとに実行
        $schedule->command('session:cleanup --force')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Laravelのデフォルトセッションクリーンアップも実行
        $schedule->command('session:clear')
            ->daily()
            ->at('03:00')
            ->withoutOverlapping();

        // キャッシュクリアを毎日実行（オプション）
        $schedule->command('cache:prune-stale-tags')
            ->hourly()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}