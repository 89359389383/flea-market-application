<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ダミーユーザーを作成
        User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
            'building' => 'サンプルビル101',
            'profile_image' => 'storage/profiles/6rd4Hg4jJQHeEIFqKB7a1cJnsgyf0Jwy8HCkvFzr.svg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
