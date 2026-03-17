<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * practice_choicesテーブルにpartカラムを追加
     * seed_data.sqlとの互換性のために必要
     */
    public function up(): void
    {
        Schema::table('practice_choices', function (Blueprint $table) {
            if (!Schema::hasColumn('practice_choices', 'part')) {
                $table->enum('part', ['1', '2', '3'])
                    ->after('question_id')
                    ->nullable()
                    ->comment('問題の部を示す（1～3）');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_choices', function (Blueprint $table) {
            if (Schema::hasColumn('practice_choices', 'part')) {
                $table->dropColumn('part');
            }
        });
    }
};
