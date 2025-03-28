<?php

namespace Tests\Unit;

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
     * ✅ 1. いいねした商品だけが表示されることを確認するテスト
     */
    public function test_only_liked_items_are_displayed()
    {
        // 1. ユーザーを作成
        $user = User::factory()->create()->first();

        // 2. いいねする商品を2つ作成し、いいねを登録
        $likedItems = Item::factory()->count(2)->create();
        foreach ($likedItems as $item) {
            Like::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
            ]);
        }

        // 3. いいねしていない商品を1つ作成
        $unlikedItem = Item::factory()->create();

        // 4. マイリストページを開く
        $response = $this->actingAs($user)->get('/?tab=mylist');

        // 5. いいねした商品が表示され、いいねしていない商品が表示されないことを確認
        $response->assertStatus(200);
        foreach ($likedItems as $item) {
            $response->assertSee($item->name);
        }
        $response->assertDontSee($unlikedItem->name);
    }

    /**
     * ✅ 2. 購入済み商品に「Sold」ラベルが表示されることを確認するテスト
     */
    public function test_purchased_items_display_sold_label()
    {
        // 1. ユーザーを作成
        $user = User::factory()->create();

        // 2. 購入済みの商品を作成
        $purchasedItem = Item::factory()->create(['sold' => true]);

        // 3. 購入履歴といいねを登録
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // 4. マイリストページを開く
        $response = $this->actingAs($user)->get('/?tab=mylist');

        // 5. 「Sold」ラベルが表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /**
     * ✅ 3. 自分が出品した商品がマイリストに表示されないことを確認するテスト
     */
    public function test_self_listed_items_are_not_displayed()
    {
        // 1. ユーザーを作成
        $user = User::factory()->create();

        // 2. 自分が出品した商品を作成
        $selfItem = Item::factory()->create(['user_id' => $user->id]);

        // 3. 他のユーザーの商品を作成し、いいねを登録
        $otherUser = User::factory()->create();
        $likedItem = Item::factory()->create(['user_id' => $otherUser->id]);
        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // 4. マイリストページを開く
        $response = $this->actingAs($user)->get('/?tab=mylist');

        // 5. 自分の出品商品が表示されず、いいねした商品が表示されることを確認
        $response->assertStatus(200);
        $response->assertDontSee($selfItem->name);
        $response->assertSee($likedItem->name);
    }

    /**
     * ✅ 4. 未ログインユーザーがマイリストにアクセスできないことを確認するテスト
     */
    public function test_guest_user_cannot_access_mylist_and_sees_nothing()
    {
        // 1. 商品を2つ作成
        $items = Item::factory()->count(2)->create();

        // 2. マイリストページにアクセス
        $response = $this->get('/?tab=mylist');

        // 3. ログインページにリダイレクトされ、商品が表示されないことを確認
        $response->assertRedirect(route('login'));
        foreach ($items as $item) {
            $response->assertDontSee($item->name);
        }
    }
}
