<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // sessionsテーブルの最適化
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                // インデックスの追加（存在しない場合のみ）
                if (!$this->indexExists('sessions', 'sessions_user_id_index')) {
                    $table->index('user_id');
                }
                if (!$this->indexExists('sessions', 'sessions_last_activity_index')) {
                    $table->index('last_activity');
                }
            });
        }

        // exam_sessionsテーブルの最適化
        if (Schema::hasTable('exam_sessions')) {
            Schema::table('exam_sessions', function (Blueprint $table) {
                // 複合インデックスの追加（クエリパフォーマンス向上）
                if (!$this->indexExists('exam_sessions', 'exam_sessions_user_event_index')) {
                    $table->index(['user_id', 'event_id'], 'exam_sessions_user_event_index');
                }
                
                if (!$this->indexExists('exam_sessions', 'exam_sessions_started_finished_index')) {
                    $table->index(['started_at', 'finished_at'], 'exam_sessions_started_finished_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('sessions_user_id_index');
                $table->dropIndex('sessions_last_activity_index');
            });
        }

        if (Schema::hasTable('exam_sessions')) {
            Schema::table('exam_sessions', function (Blueprint $table) {
                $table->dropIndex('exam_sessions_user_event_index');
                $table->dropIndex('exam_sessions_started_finished_index');
            });
        }
    }

    /**
     * インデックスが存在するかチェック
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
        
        return isset($indexes[$index]);
    }
};