<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * パフォーマンス向上とデッドロック防止のためのインデックス追加
     */
    public function up(): void
    {
        // ★1. exam_sessions テーブルのインデックス追加
        Schema::table('exam_sessions', function (Blueprint $table) {
            // user_id + event_id + finished_at の複合インデックス
            if (!$this->hasIndex('exam_sessions', 'idx_user_event_status')) {
                DB::statement('ALTER TABLE exam_sessions ADD INDEX idx_user_event_status (user_id, event_id, finished_at)');
            }
            
            // user_id + finished_at の複合インデックス
            if (!$this->hasIndex('exam_sessions', 'idx_user_finished')) {
                DB::statement('ALTER TABLE exam_sessions ADD INDEX idx_user_finished (user_id, finished_at)');
            }
        });
        
        // session_uuid カラムとユニークインデックス
        if (!Schema::hasColumn('exam_sessions', 'session_uuid')) {
            DB::statement('ALTER TABLE exam_sessions ADD session_uuid CHAR(36) NULL');
            DB::statement('ALTER TABLE exam_sessions ADD UNIQUE exam_sessions_session_uuid_unique (session_uuid)');
        } elseif (!$this->hasIndex('exam_sessions', 'exam_sessions_session_uuid_unique')) {
            DB::statement('ALTER TABLE exam_sessions ADD UNIQUE exam_sessions_session_uuid_unique (session_uuid)');
        }

        // ★2. questions テーブルのインデックス追加
        if (!$this->hasIndex('questions', 'idx_part_number')) {
            DB::statement('ALTER TABLE questions ADD INDEX idx_part_number (part, number)');
        }

        // ★3. choices テーブルのインデックス追加
        if (!$this->hasIndex('choices', 'idx_question_part')) {
            DB::statement('ALTER TABLE choices ADD INDEX idx_question_part (question_id, part)');
        }
        
        if (!$this->hasIndex('choices', 'idx_part_correct')) {
            DB::statement('ALTER TABLE choices ADD INDEX idx_part_correct (part, is_correct)');
        }

        // ★4. answers テーブルのインデックス追加
        if (!$this->hasIndex('answers', 'idx_user_session')) {
            DB::statement('ALTER TABLE answers ADD INDEX idx_user_session (user_id, exam_session_id)');
        }
        
        if (!$this->hasIndex('answers', 'idx_session_part')) {
            DB::statement('ALTER TABLE answers ADD INDEX idx_session_part (exam_session_id, part)');
        }
        
        if (!$this->hasIndex('answers', 'idx_user_question')) {
            DB::statement('ALTER TABLE answers ADD UNIQUE idx_user_question (user_id, question_id)');
        }

        // ★5. events テーブルのインデックス追加
        if (!$this->hasIndex('events', 'idx_passphrase_dates')) {
            DB::statement('ALTER TABLE events ADD INDEX idx_passphrase_dates (passphrase, begin, end)');
        }

        // ★6. exam_violations テーブルのインデックス追加
        if (!$this->hasIndex('exam_violations', 'idx_session')) {
            DB::statement('ALTER TABLE exam_violations ADD INDEX idx_session (exam_session_id)');
        }
        
        if (!$this->hasIndex('exam_violations', 'idx_user_created')) {
            DB::statement('ALTER TABLE exam_violations ADD INDEX idx_user_created (user_id, created_at)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->hasIndex('exam_sessions', 'idx_user_event_status')) {
            DB::statement('ALTER TABLE exam_sessions DROP INDEX idx_user_event_status');
        }
        if ($this->hasIndex('exam_sessions', 'idx_user_finished')) {
            DB::statement('ALTER TABLE exam_sessions DROP INDEX idx_user_finished');
        }
        if ($this->hasIndex('exam_sessions', 'exam_sessions_session_uuid_unique')) {
            DB::statement('ALTER TABLE exam_sessions DROP INDEX exam_sessions_session_uuid_unique');
        }

        if ($this->hasIndex('questions', 'idx_part_number')) {
            DB::statement('ALTER TABLE questions DROP INDEX idx_part_number');
        }

        if ($this->hasIndex('choices', 'idx_question_part')) {
            DB::statement('ALTER TABLE choices DROP INDEX idx_question_part');
        }
        if ($this->hasIndex('choices', 'idx_part_correct')) {
            DB::statement('ALTER TABLE choices DROP INDEX idx_part_correct');
        }

        if ($this->hasIndex('answers', 'idx_user_session')) {
            DB::statement('ALTER TABLE answers DROP INDEX idx_user_session');
        }
        if ($this->hasIndex('answers', 'idx_session_part')) {
            DB::statement('ALTER TABLE answers DROP INDEX idx_session_part');
        }
        if ($this->hasIndex('answers', 'idx_user_question')) {
            DB::statement('ALTER TABLE answers DROP INDEX idx_user_question');
        }

        if ($this->hasIndex('events', 'idx_passphrase_dates')) {
            DB::statement('ALTER TABLE events DROP INDEX idx_passphrase_dates');
        }

        if ($this->hasIndex('exam_violations', 'idx_session')) {
            DB::statement('ALTER TABLE exam_violations DROP INDEX idx_session');
        }
        if ($this->hasIndex('exam_violations', 'idx_user_created')) {
            DB::statement('ALTER TABLE exam_violations DROP INDEX idx_user_created');
        }
    }

    /**
     * インデックスが存在するかチェック
     */
    private function hasIndex(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return !empty($indexes);
    }
};