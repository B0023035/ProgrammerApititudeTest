<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // デフォルト管理者アカウント（初期インストール用）
        Admin::firstOrCreate(
            ['email' => 'admin@provisional'],
            [
                'name' => 'admin',
                'password' => Hash::make('P@ssw0rd'),
                'role' => 'super_admin',
            ]
        );

        $this->command->info('管理者アカウントを作成しました。');
        $this->command->info('Email: admin@provisional / Password: P@ssw0rd');
        $this->command->warn('※ 初回ログイン後、パスワードとメールアドレスを変更してください。');
    }
}
