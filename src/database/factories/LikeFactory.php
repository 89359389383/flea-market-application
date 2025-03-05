<?php

namespace Database\Factories;

use App\Models\Like;
use App\Models\User;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikeFactory extends Factory
{
    protected $model = Like::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->create()->id, // 確実にIDを設定
            'item_id' => Item::factory()->create()->id, // 確実にIDを設定
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
