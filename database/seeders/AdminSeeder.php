<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // デフォルト管理者アカウント
        Admin::firstOrCreate(
            ['email' => 'admin@a'],
            [
                'name' => 'admin',
                'password' => Hash::make('Passw0rd'),
            ]
        );

        // テスト用管理者
        Admin::firstOrCreate(
            ['email' => 'a@a'],
            [
                'name' => 'a',
                'password' => Hash::make('Passw0rd'),
            ]
        );

        $this->command->info('管理者アカウントを作成しました。');
        $this->command->info('Email: admin@a / Password: Passw0rd');
        $this->command->info('Email: a@a / Password: Passw0rd');
    }
}