<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired sessions and exam sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Do you want to cleanup expired sessions?')) {
            $this->info('Cleanup cancelled.');
            return 0;
        }

        $this->info('Starting session cleanup...');

        // 1. 期限切れセッションのクリーンアップ
        $expiredSessions = $this->cleanupExpiredSessions();
        
        // 2. 孤立したexam_sessionsのクリーンアップ
        $orphanedExamSessions = $this->cleanupOrphanedExamSessions();
        
        // 3. 古い完了済みexam_sessionsのクリーンアップ（30日以上前）
        $oldCompletedSessions = $this->cleanupOldCompletedSessions();

        $this->newLine();
        $this->info('Cleanup completed:');
        $this->line("  - Expired sessions: {$expiredSessions}");
        $this->line("  - Orphaned exam sessions: {$orphanedExamSessions}");
        $this->line("  - Old completed sessions: {$oldCompletedSessions}");

        return 0;
    }

    /**
     * 期限切れセッションのクリーンアップ
     */
    private function cleanupExpiredSessions(): int
    {
        $lifetime = config('session.lifetime', 720); // minutes
        $expiredTime = Carbon::now()->subMinutes($lifetime)->timestamp;

        $count = DB::table('sessions')
            ->where('last_activity', '<', $expiredTime)
            ->delete();

        $this->info("Cleaned up {$count} expired sessions.");
        return $count;
    }

    /**
     * 孤立したexam_sessionsのクリーンアップ
     * （対応するsessionsが存在しない、または24時間以上前に開始されて未完了）
     */
    private function cleanupOrphanedExamSessions(): int
    {
        $count = DB::table('exam_sessions')
            ->where('started_at', '<', Carbon::now()->subHours(24))
            ->whereNull('finished_at')
            ->whereNull('disqualified_at')
            ->delete();

        $this->info("Cleaned up {$count} orphaned exam sessions.");
        return $count;
    }

    /**
     * 古い完了済みexam_sessionsのクリーンアップ（オプション）
     * 注意: このメソッドはデータ保持ポリシーに応じて調整してください
     */
    private function cleanupOldCompletedSessions(): int
    {
        // 90日以上前に完了したセッションを削除
        // 本番環境では削除せずアーカイブすることを推奨
        $count = DB::table('exam_sessions')
            ->where(function($query) {
                $query->whereNotNull('finished_at')
                      ->orWhereNotNull('disqualified_at');
            })
            ->where(function($query) {
                $query->where('finished_at', '<', Carbon::now()->subDays(90))
                      ->orWhere('disqualified_at', '<', Carbon::now()->subDays(90));
            })
            ->delete();

        $this->info("Cleaned up {$count} old completed exam sessions.");
        return $count;
    }
}