<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Like;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyListTest extends TestCase
{
    use RefreshDatabase; // 各テスト実行前にデータベースをリセットする

    /**
     * いいねした商品だけが表示されることを確認する
     */
    // 修正箇所：'/mylist' を '/?tab=mylist' に変更

    public function test_only_liked_items_are_displayed()
    {
        $user = User::factory()->create()->first();

        $likedItems = Item::factory()->count(2)->create();
        foreach ($likedItems as $item) {
            Like::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
            ]);
        }

        $unlikedItem = Item::factory()->create();

        // ✅ 修正：存在しない /mylist → 正しいパス '/?tab=mylist'
        $response = $this->actingAs($user)->get('/?tab=mylist'); // ← 修正済み

        $response->assertStatus(200);
        foreach ($likedItems as $item) {
            $response->assertSee($item->name);
        }

        $response->assertDontSee($unlikedItem->name);
    }

    public function test_purchased_items_display_sold_label()
    {
        $user = User::factory()->create();

        $purchasedItem = Item::factory()->create(['sold' => true]);

        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // ✅ 修正：'/mylist' → '/?tab=mylist'
        $response = $this->actingAs($user)->get('/?tab=mylist'); // ← 修正済み

        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    public function test_self_listed_items_are_not_displayed()
    {
        $user = User::factory()->create();

        $selfItem = Item::factory()->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        $likedItem = Item::factory()->create(['user_id' => $otherUser->id]);
        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // ✅ 修正：'/mylist' → '/?tab=mylist'
        $response = $this->actingAs($user)->get('/?tab=mylist'); // ← 修正済み

        $response->assertStatus(200);
        $response->assertDontSee($selfItem->name);
        $response->assertSee($likedItem->name);
    }

    public function test_guest_user_cannot_access_mylist_and_sees_nothing()
    {
        $items = Item::factory()->count(2)->create();

        // ✅ 修正：'/mylist' → '/?tab=mylist'
        $response = $this->get('/?tab=mylist'); // ← 修正済み

        $response->assertRedirect(route('login'));
        foreach ($items as $item) {
            $response->assertDontSee($item->name);
        }
    }
}
