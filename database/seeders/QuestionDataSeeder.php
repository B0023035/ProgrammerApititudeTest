<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionDataSeeder extends Seeder
{
    /**
     * 問題データをシードする
     * questions, choices, practice_questions, practice_choices
     */
    public function run(): void
    {
        $sqlFile = database_path('seeders/seed_data.sql');
        
        if (!file_exists($sqlFile)) {
            $this->command->error('seed_data.sql が見つかりません。');
            return;
        }

        $sql = file_get_contents($sqlFile);
        
        // 外部キー制約を一時的に無効化
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // 既存データをクリア（順序重要）
        DB::table('choices')->truncate();
        DB::table('questions')->truncate();
        DB::table('practice_choices')->truncate();
        DB::table('practice_questions')->truncate();
        
        // SQLを実行
        DB::unprepared($sql);
        
        // 外部キー制約を有効化
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('問題データをシードしました。');
        $this->command->info('- questions: ' . DB::table('questions')->count() . '件');
        $this->command->info('- choices: ' . DB::table('choices')->count() . '件');
        $this->command->info('- practice_questions: ' . DB::table('practice_questions')->count() . '件');
        $this->command->info('- practice_choices: ' . DB::table('practice_choices')->count() . '件');
    }
}
