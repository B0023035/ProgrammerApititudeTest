<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'B0023035',
            'email' => 'B0023035@ib.yic.ac.jp',
            'password' => 'Passw0rd'
        ]);

        User::create([
            'name' => 'B0023000',
            'email' => 'B0023000@ib.yic.ac.jp',
            'password' => 'Passw0rd'
        ]);
    }
}
