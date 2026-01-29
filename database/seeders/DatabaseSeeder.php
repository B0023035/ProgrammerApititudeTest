<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 初期インストール時に必要なデータを投入
     */
    public function run(): void
    {
        $this->call([
            // 管理者アカウント作成
            AdminSeeder::class,
            // 問題データ投入
            QuestionDataSeeder::class,
        ]);
    }
}
