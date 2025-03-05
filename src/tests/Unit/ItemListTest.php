<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;

class ItemListTest extends TestCase
{
    use RefreshDatabase; // テストごとにデータベースをリセットする

    /**
     * ✅ 1. 全ての商品が取得できるか確認するテスト
     */
    public function test_all_items_are_displayed()
    {
        // ✅ 1. まずユーザーを作成
        $user = User::factory()->create();

        // ✅ 2. 作成したユーザーを紐づけて商品を3つ作成
        Item::factory()->count(3)->create(['user_id' => $user->id]);

        // ✅ 3. 商品一覧ページを開く（APIを叩く）
        $response = $this->get('/');

        // ✅ 4. ステータスコード 200（成功）が返ることを確認
        $response->assertStatus(200);

        // ✅ 5. データベースに登録されている3つの商品が全て表示されているか確認
        $items = Item::all();
        foreach ($items as $item) {
            $response->assertSee($item->name);
        }
    }

    /**
     * ✅ 2. 購入済み商品には「Sold」ラベルが表示されるか確認するテスト
     */
    public function test_sold_items_have_sold_label()
    {
        // 1. 商品を1つ作成
        $item = Item::factory()->create(['sold' => false]);

        // 2. ユーザーを作成し、そのユーザーが商品を購入したことにする
        $user = User::factory()->create();
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id
        ]);

        // 3. 購入済み商品は `sold` フラグを `true` に更新
        $item->update(['sold' => true]);

        // 4. 商品一覧ページを開く
        $response = $this->get('/');

        // 5. 「Sold」という文字が表示されているかチェック
        $response->assertSee('Sold');
    }

    /**
     * ✅ 3. 自分が出品した商品が一覧に表示されないか確認するテスト
     */
    public function test_user_does_not_see_their_own_items()
    {
        // 1. ユーザーを作成
        $user = User::factory()->create();

        // 2. ユーザーが出品した商品を作成
        $ownItem = Item::factory()->create(['user_id' => $user->id]);

        // 3. 別のユーザーでログイン（自分の出品商品が見えないようにする）
        $anotherUser = User::factory()->create()->first();
        $this->actingAs($anotherUser);

        // 4. 商品一覧ページを開く
        $response = $this->get('/');

        // 5. 自分が出品した商品の名前が表示されていないことを確認
        $response->assertDontSee($ownItem->name);
    }
}
