<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(), // ✅ 事前に作成されたユーザーのIDを取得
            'brand_name' => $this->faker->word,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'condition' => $this->faker->randomElement(['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い']),
            'price' => $this->faker->numberBetween(1000, 50000),
            'image' => $this->faker->imageUrl(),
            'sold' => false,
            'likes_count' => $this->faker->numberBetween(0, 100),
            'comments_count' => $this->faker->numberBetween(0, 50),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
