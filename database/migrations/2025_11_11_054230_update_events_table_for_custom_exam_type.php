<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // exam_typeにcustomを追加
            $table->enum('exam_type', ['30min', '45min', 'full', 'custom'])
                ->default('full')
                ->change();
            
            // カスタム設定用のカラムを追加
            $table->integer('custom_part1_questions')->nullable()->after('exam_type');
            $table->integer('custom_part1_time')->nullable()->after('custom_part1_questions');
            $table->integer('custom_part2_questions')->nullable()->after('custom_part1_time');
            $table->integer('custom_part2_time')->nullable()->after('custom_part2_questions');
            $table->integer('custom_part3_questions')->nullable()->after('custom_part2_time');
            $table->integer('custom_part3_time')->nullable()->after('custom_part3_questions');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'custom_part1_questions',
                'custom_part1_time',
                'custom_part2_questions',
                'custom_part2_time',
                'custom_part3_questions',
                'custom_part3_time',
            ]);
            
            $table->enum('exam_type', ['30min', '45min', 'full'])
                ->default('full')
                ->change();
        });
    }
};