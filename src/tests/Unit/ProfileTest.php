<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Trade;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 購入済み商品が取引完了後に購入履歴ページで表示されるか確認するテスト
     */
    public function test_purchased_items_are_displayed_on_buy_page_after_trade_completed()
    {
        // 1. テストユーザー（購入者/出品者）作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 2. ユーザーが出品した商品を2つ作成
        $item1 = Item::factory()->create(['user_id' => $user->id, 'name' => '自分の商品1']);
        $item2 = Item::factory()->create(['user_id' => $user->id, 'name' => '自分の商品2']);

        // 3. 他ユーザー（出品者）作成 & 購入対象商品作成
        /** @var \App\Models\User $seller */
        $seller = User::factory()->create();
        $purchasedItem = Item::factory()->create(['user_id' => $seller->id, 'name' => '購入商品A']);

        // 4. 購入処理POST（Purchase/Tradeレコード作成）
        $this->actingAs($user)->post(route('purchase.store', ['item_id' => $purchasedItem->id]), [
            'postal_code' => '111-2222',
            'address' => '東京都中央区',
            'building' => 'テストビル10F',
            'payment_method' => 'コンビニ払い',
        ]);

        // 5. Tradeレコード取得
        $trade = Trade::where('item_id', $purchasedItem->id)->first();
        $this->assertNotNull($trade, 'Tradeレコードが作成されていること');

        // 6. 購入者が評価
        $this->actingAs($user)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);

        // 7. 出品者が評価
        $this->actingAs($seller)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        $this->actingAs($user)->get(route('trade.chat.show', $trade->id));
        $trade->refresh();
        $this->assertTrue((bool)$trade->is_completed, 'Tradeのis_completedがtrueになっていること');

        // 8. プロフィールページ（デフォルトはsellタブ）
        $response = $this->actingAs($user)->get(route('user.show'));
        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->profile_image)
            ->assertSee($item1->name)
            ->assertSee($item2->name)
            ->assertDontSee($purchasedItem->name); // 購入商品はsellタブには表示されない

        // 9. 購入履歴（buyタブ）で購入商品が表示される
        $response = $this->actingAs($user)->get(route('user.show', ['page' => 'buy']));
        $response->assertStatus(200)
            ->assertSee($purchasedItem->name);
    }
}
