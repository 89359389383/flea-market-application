<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Facades\Log;

class ProfileTest extends TestCase
{
    use RefreshDatabase; // テストごとにデータベースをリセットする

    /** @test */
    public function 購入した商品がクリックされたときに表示される()
    {
        $user = User::factory()->create([
            'profile_image' => 'path/to/profile_image.jpg',
        ])->first();

        $item1 = Item::factory()->create(['user_id' => $user->id]);
        $item2 = Item::factory()->create(['user_id' => $user->id]);

        $purchasedItem = Item::factory()->create();
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('user.show'));

        Log::info('プロフィール画像のパス:', ['profile_image' => $user->profile_image]);

        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->profile_image)
            ->assertSee($item1->name)
            ->assertSee($item2->name)
            ->assertDontSee($purchasedItem->name);

        $response = $this->get(route('user.show', ['page' => 'buy']));

        $response->assertStatus(200)
            ->assertSee($purchasedItem->name);
    }
}
