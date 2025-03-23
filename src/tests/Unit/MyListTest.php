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
    public function test_only_liked_items_are_displayed()
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create()->first();

        // いいねされた商品を2つ作成
        $likedItems = Item::factory()->count(2)->create();
        foreach ($likedItems as $item) {
            Like::create([ // 明示的に `create()` を使う
                'user_id' => $user->id,
                'item_id' => $item->id,
            ]);
        }

        // いいねしていない商品を1つ作成
        $unlikedItem = Item::factory()->create();

        // ユーザーとしてログインしてリクエストを送る
        $response = $this->actingAs($user)->get('/mylist');

        // レスポンスに いいねした商品の情報が含まれているか確認
        $response->assertStatus(200);
        foreach ($likedItems as $item) {
            $response->assertSee($item->name);
        }

        // いいねしていない商品が含まれていないことを確認
        $response->assertDontSee($unlikedItem->name);
    }

    /**
     * 購入済み商品に「Sold」のラベルが表示されることを確認する
     */
    public function test_purchased_items_display_sold_label()
    {
        $user = User::factory()->create();

        // 購入済みの商品を作成（sold=true）
        $purchasedItem = Item::factory()->create(['sold' => true]);

        // ユーザーの購入記録を作成
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // いいねした商品として登録
        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // ログインしてマイリストページにアクセス
        $user = User::factory()->create()->first();
        $response = $this->actingAs($user)->get('/mylist');

        // レスポンスに "Sold" ラベルが含まれているか確認
        $response->assertStatus(200);
        $response->assertSee('Sold');
    }

    /**
     * 自分が出品した商品はマイリストに表示されないことを確認する
     */
    public function test_self_listed_items_are_not_displayed()
    {
        $user = User::factory()->create();

        // ユーザーが出品した商品を作成
        $selfItem = Item::factory()->create(['user_id' => $user->id]);

        // 他のユーザーが出品した商品（いいね済み）を作成
        $otherUser = User::factory()->create();
        $likedItem = Item::factory()->create(['user_id' => $otherUser->id]);
        Like::factory()->create([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // ログインしてマイリストページにアクセス
        $user = User::factory()->create()->first();
        $response = $this->actingAs($user)->get('/mylist');

        // 自分が出品した商品が表示されていないことを確認
        $response->assertStatus(200);
        $response->assertDontSee($selfItem->name);

        // いいねした他のユーザーの商品は表示される
        $response->assertSee($likedItem->name);
    }

    public function test_guest_user_cannot_access_mylist_and_sees_nothing()
    {
        // 商品をいくつか作成（表示されてはならない）
        $items = Item::factory()->count(2)->create();

        // 🔽 未ログイン状態でマイリストページにアクセス
        $response = $this->get('/mylist');

        // 🔽 ログインページにリダイレクトされることを確認
        $response->assertRedirect(route('login'));

        // 🔽 レスポンス本文に商品名が含まれていない（商品が表示されない）ことを確認
        foreach ($items as $item) {
            $response->assertDontSee($item->name);
        }
    }
}
