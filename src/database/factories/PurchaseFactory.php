<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * モデルのデフォルトの状態を定義
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(), // 購入者
            'item_id' => Item::factory(), // 購入した商品
            'postal_code' => $this->faker->postcode,
            'address' => $this->faker->address,
            'building' => $this->faker->word,
            'payment_method' => $this->faker->randomElement(['コンビニ払い', 'カード払い']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
