<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既存のセッションで grade が NULL のものに対して、user の admission_year から学年を計算して埋める
        DB::statement(<<<'SQL'
            UPDATE exam_sessions es
            LEFT JOIN users u ON es.user_id = u.id
            SET es.grade = CASE
                WHEN u.admission_year IS NULL THEN NULL
                WHEN u.admission_year = 0 THEN NULL
                ELSE LEAST(GREATEST(YEAR(CURDATE()) - u.admission_year + 1, 1), 10)
            END
            WHERE es.grade IS NULL
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ロールバック時は grade を NULL に戻す
        DB::statement('UPDATE exam_sessions SET grade = NULL');
    }
};
