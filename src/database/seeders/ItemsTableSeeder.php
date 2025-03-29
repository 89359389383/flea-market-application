<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\User;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // `UsersTableSeeder` で作成したユーザーを取得
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            // ユーザーが存在しない場合はエラーを出す（デバッグ用）
            throw new \Exception("ユーザーが見つかりません。先に UsersTableSeeder を実行してください。");
        }

        // 商品データリスト
        $items = [
            [
                'user_id' => $user->id,
                'brand_name' => 'Armani',
                'name' => '腕時計',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'condition' => '良好',
                'price' => 15000,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'HDD',
                'description' => '高速で信頼性の高いハードディスク',
                'condition' => '目立った傷や汚れなし',
                'price' => 5000,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => '玉ねぎ3束',
                'description' => '新鮮な玉ねぎ3束のセット',
                'condition' => 'やや傷や汚れあり',
                'price' => 300,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => '革靴',
                'description' => 'クラシックなデザインの革靴',
                'condition' => '状態が悪い',
                'price' => 4000,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'ノートPC',
                'description' => '高性能なノートパソコン',
                'condition' => '良好',
                'price' => 45000,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'マイク',
                'description' => '高音質のレコーディング用マイク',
                'condition' => '目立った傷や汚れなし',
                'price' => 8000,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'ショルダーバッグ',
                'description' => 'おしゃれなショルダーバッグ',
                'condition' => 'やや傷や汚れあり',
                'price' => 3500,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'タンブラー',
                'description' => '使いやすいタンブラー',
                'condition' => '状態が悪い',
                'price' => 500,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'コーヒーミル',
                'description' => '手動のコーヒーミル',
                'condition' => '良好',
                'price' => 4000,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'brand_name' => null,
                'name' => 'メイクセット',
                'description' => '便利なメイクアップセット',
                'condition' => '目立った傷や汚れなし',
                'price' => 2500,
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg',
                'sold' => false,
                'likes_count' => 0,
                'comments_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
