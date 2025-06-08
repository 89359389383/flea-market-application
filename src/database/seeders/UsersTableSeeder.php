<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 固定のパスワード文字列を変数に格納
        $userPasswordA = 'Kz8#rTq55@LmWv4z'; // ユーザーA用パスワード
        $userPasswordB = 'Xy9$wRs22@LtPv6m'; // ユーザーB用パスワード
        $userPasswordC = 'Zq7%aFt99#KmRx3b'; // ユーザーC用パスワード（何も紐付けないユーザー）

        // 【1】ユーザーA（出品者A）
        User::updateOrCreate(
            ['email' => 'sellerA@example.com'], // 条件：このメールが既にあるか
            [
                'name' => '出品者A',
                'password' => bcrypt($userPasswordA), // 固定パスワードをハッシュ化して保存
                'postal_code' => '123-4567',
                'address' => '東京都新宿区',
                'building' => 'Aビル101',
                'profile_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 【2】ユーザーB（出品者B）
        User::updateOrCreate(
            ['email' => 'sellerB@example.com'],
            [
                'name' => '出品者B',
                'password' => bcrypt($userPasswordB),
                'postal_code' => '987-6543',
                'address' => '大阪府大阪市',
                'building' => 'Bビル202',
                'profile_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 【3】ユーザーC（紐付けなしゲストユーザー）
        User::updateOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'ゲストユーザー',
                'password' => bcrypt($userPasswordC),
                'postal_code' => '555-5555',
                'address' => '北海道札幌市',
                'building' => 'Cビル303',
                'profile_image' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
