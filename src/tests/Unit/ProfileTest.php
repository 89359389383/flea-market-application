<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Trade;
use Illuminate\Support\Facades\Log;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 購入済み商品が取引完了後に購入履歴ページで表示されるか確認するテスト
     */
    public function test_purchased_items_are_displayed_on_buy_page_after_trade_completed()
    {
        Log::debug('テスト開始: test_purchased_items_are_displayed_on_buy_page_after_trade_completed');

        // 1. テストユーザー（購入者/出品者）作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'profile_image' => 'path/to/profile_image.jpg',
        ]);
        Log::debug('テストユーザー作成', ['user_id' => $user->id]);

        // 2. ユーザーが出品した商品を2つ作成
        $item1 = Item::factory()->create(['user_id' => $user->id, 'name' => '自分の商品1']);
        $item2 = Item::factory()->create(['user_id' => $user->id, 'name' => '自分の商品2']);
        Log::debug('自分の商品作成', ['item1_id' => $item1->id, 'item2_id' => $item2->id]);

        // 3. 他ユーザー（出品者）作成 & 購入対象商品作成
        /** @var \App\Models\User $seller */
        $seller = User::factory()->create();
        $purchasedItem = Item::factory()->create(['user_id' => $seller->id, 'name' => '購入商品A']);
        Log::debug('購入商品と出品者作成', ['purchased_item_id' => $purchasedItem->id, 'seller_id' => $seller->id]);

        // 4. 購入処理POST（Purchase/Tradeレコード作成）
        $this->actingAs($user)->post(route('purchase.store', ['item_id' => $purchasedItem->id]), [
            'postal_code' => '111-2222',
            'address' => '東京都中央区',
            'building' => 'テストビル10F',
            'payment_method' => 'コンビニ払い',
        ]);
        Log::debug('購入処理POST完了');

        // 5. Tradeレコード取得
        $trade = Trade::where('item_id', $purchasedItem->id)->first();
        $this->assertNotNull($trade, 'Tradeレコードが作成されていること');
        Log::debug('Tradeレコード取得', ['trade_id' => $trade->id]);

        // 6. 購入者が評価
        $this->actingAs($user)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        Log::debug('購入者評価POST完了');

        // 7. 出品者が評価
        $this->actingAs($seller)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        Log::debug('出品者評価POST完了');

        // === ここが絶対必要！ ===
        // チャット画面GETでis_completedフラグが立つ
        $this->actingAs($user)->get(route('trade.chat.show', $trade->id));
        $trade->refresh();
        $this->assertTrue((bool)$trade->is_completed, 'Tradeのis_completedがtrueになっていること');
        Log::debug('Trade完了状態確認', ['is_completed' => $trade->is_completed]);

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
        Log::debug('購入履歴ページで購入商品表示確認OK');

        Log::debug('テスト終了: test_purchased_items_are_displayed_on_buy_page_after_trade_completed');
    }
}
