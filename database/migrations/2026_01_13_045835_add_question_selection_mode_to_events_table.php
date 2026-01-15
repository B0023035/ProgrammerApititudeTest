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
        Schema::table('events', function (Blueprint $table) {
            // 問題選択モード: 'random'=ランダム出題, 'custom'=指定問題
            $table->string('question_selection_mode', 20)
                ->default('random')
                ->after('exam_type')
                ->comment('問題選択モード: random=ランダム, custom=指定');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('question_selection_mode');
        });
    }
};
