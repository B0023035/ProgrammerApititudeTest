<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // カラムが存在しない場合のみ追加
            if (!Schema::hasColumn('exam_sessions', 'disqualified_at')) {
                $table->timestamp('disqualified_at')->nullable()->after('finished_at');
            }
            
            if (!Schema::hasColumn('exam_sessions', 'disqualification_reason')) {
                $table->string('disqualification_reason', 500)->nullable()->after('disqualified_at');
            }
            
            if (!Schema::hasColumn('exam_sessions', 'security_log')) {
                $table->json('security_log')->nullable()->after('remaining_time');
            }
        });
        
        // インデックスを安全に追加
        $indexExists = DB::select("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'exam_sessions' 
            AND INDEX_NAME = 'idx_user_disqualified'
        ");
        
        if ($indexExists[0]->count == 0) {
            Schema::table('exam_sessions', function (Blueprint $table) {
                $table->index(['user_id', 'disqualified_at'], 'idx_user_disqualified');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // インデックス削除
            $indexExists = DB::select("
                SELECT COUNT(*) as count 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'exam_sessions' 
                AND INDEX_NAME = 'idx_user_disqualified'
            ");
            
            if ($indexExists[0]->count > 0) {
                $table->dropIndex('idx_user_disqualified');
            }
            
            // カラム削除
            if (Schema::hasColumn('exam_sessions', 'security_log')) {
                $table->dropColumn('security_log');
            }
            
            if (Schema::hasColumn('exam_sessions', 'disqualification_reason')) {
                $table->dropColumn('disqualification_reason');
            }
            
            if (Schema::hasColumn('exam_sessions', 'disqualified_at')) {
                $table->dropColumn('disqualified_at');
            }
        });
    }
};