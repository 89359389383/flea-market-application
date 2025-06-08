<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. ログインユーザーが商品を購入できるか確認するテスト
     */
    public function test_logged_in_user_can_purchase_item()
    {
        $user = User::factory()->create()->first();
        $item = Item::factory()->create(['sold' => false]);

        $response = $this->actingAs($user)
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',
                'address' => '東京都渋谷区',
                'building' => '渋谷タワー',
                'payment_method' => 'コンビニ払い',
            ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    /**
     * ✅ 2. 購入後に商品一覧に「Sold」と表示されるか確認するテスト
     */
    public function test_sold_label_appears_after_purchase()
    {
        $user = User::factory()->create()->first();
        $item = Item::factory()->create(['sold' => false]);

        $this->actingAs($user)
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',
                'address' => '東京都渋谷区',
                'building' => '渋谷タワー',
                'payment_method' => 'コンビニ払い',
            ]);

        $this->get(route('items.index'))
            ->assertSee('Sold');
    }

    /**
     * ✅ 3. 購入後、プロフィールの購入履歴に追加されるか確認するテスト
     */
    public function test_purchase_history_is_added_after_trade_completed()
    {
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create();
        /** @var \App\Models\User $seller */
        $seller = User::factory()->create();

        $item = Item::factory()->create([
            'sold' => false,
            'user_id' => $seller->id,
            'name' => 'テスト商品A',
        ]);

        $response = $this->actingAs($buyer)->post(route('purchase.store', ['item_id' => $item->id]), [
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
            'building' => 'テストビル202',
            'payment_method' => 'コンビニ払い',
        ]);
        $response->assertRedirect(route('items.index'));

        $trade = \App\Models\Trade::where('item_id', $item->id)->first();
        $this->assertNotNull($trade, 'Tradeレコードが作成されていること');

        $responseBuyerEval = $this->actingAs($buyer)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        $responseBuyerEval->assertRedirect(route('trade.chat.show', $trade->id));

        $responseSellerEval = $this->actingAs($seller)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        $responseSellerEval->assertRedirect(route('trade.chat.show', $trade->id));

        $this->actingAs($buyer)->get(route('trade.chat.show', $trade->id));
        $trade->refresh();
        $this->assertTrue((bool)$trade->is_completed, 'Tradeのis_completedがtrueになっていること');

        $responseBuyList = $this->actingAs($buyer)->get(route('user.show', ['page' => 'buy']));
        $responseBuyList->assertSee('テスト商品A');
    }
}
