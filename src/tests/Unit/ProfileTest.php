<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;

class ProfileTest extends TestCase
{
    use RefreshDatabase; // テストごとにデータベースをリセットする

    /**
     * ✅ 1. 購入した商品がクリックされたときに表示されるか確認するテスト
     */
    public function test_purchased_items_are_displayed_when_clicked()
    {
        // 1. プロフィール画像を持つユーザーを作成
        $user = User::factory()->create([
            'profile_image' => 'path/to/profile_image.jpg',
        ])->first();

        // 2. ユーザーの出品商品を2つ作成
        $item1 = Item::factory()->create(['user_id' => $user->id]);
        $item2 = Item::factory()->create(['user_id' => $user->id]);

        // 3. 購入済み商品を1つ作成
        $purchasedItem = Item::factory()->create();
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // 4. ユーザーとしてログイン
        $this->actingAs($user);

        // 5. プロフィールページを開く
        $response = $this->get(route('user.show'));

        // 6. レスポンスの検証
        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->profile_image)
            ->assertSee($item1->name)
            ->assertSee($item2->name)
            ->assertDontSee($purchasedItem->name);

        // 7. 購入履歴ページを開く
        $response = $this->get(route('user.show', ['page' => 'buy']));

        // 8. 購入済み商品が表示されていることを確認
        $response->assertStatus(200)
            ->assertSee($purchasedItem->name);
    }
}
