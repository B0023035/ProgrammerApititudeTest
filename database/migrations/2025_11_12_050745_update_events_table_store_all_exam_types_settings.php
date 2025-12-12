<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // カラム名を変更してより汎用的に
            $table->renameColumn('custom_part1_questions', 'part1_questions');
            $table->renameColumn('custom_part1_time', 'part1_time');
            $table->renameColumn('custom_part2_questions', 'part2_questions');
            $table->renameColumn('custom_part2_time', 'part2_time');
            $table->renameColumn('custom_part3_questions', 'part3_questions');
            $table->renameColumn('custom_part3_time', 'part3_time');
        });

        // 既存データの更新
        DB::table('events')->get()->each(function ($event) {
            $updates = match($event->exam_type) {
                'full' => [
                    'part1_questions' => 40,
                    'part1_time' => 600,
                    'part2_questions' => 30,
                    'part2_time' => 900,
                    'part3_questions' => 25,
                    'part3_time' => 1800,
                ],
                '45min' => [
                    'part1_questions' => 30,
                    'part1_time' => 450,
                    'part2_questions' => 20,
                    'part2_time' => 600,
                    'part3_questions' => 15,
                    'part3_time' => 1080,
                ],
                '30min' => [
                    'part1_questions' => 20,
                    'part1_time' => 300,
                    'part2_questions' => 13,
                    'part2_time' => 390,
                    'part3_questions' => 10,
                    'part3_time' => 720,
                ],
                default => [] // customの場合は既存の値を保持
            };

            if (!empty($updates)) {
                DB::table('events')
                    ->where('id', $event->id)
                    ->update($updates);
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('part1_questions', 'custom_part1_questions');
            $table->renameColumn('part1_time', 'custom_part1_time');
            $table->renameColumn('part2_questions', 'custom_part2_questions');
            $table->renameColumn('part2_time', 'custom_part2_time');
            $table->renameColumn('part3_questions', 'custom_part3_questions');
            $table->renameColumn('part3_time', 'custom_part3_time');
        });
    }
};