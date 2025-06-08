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
        // 1. ユーザーを1件作成（テスト用のログインユーザー）
        $user = User::factory()->create()->first();

        // 2. 購入対象の商品を作成（売却済みでない状態）
        $item = Item::factory()->create(['sold' => false]);

        // 3. ログインユーザーとして購入処理のPOSTリクエストを送信
        $response = $this->actingAs($user)
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',
                'address' => '東京都渋谷区',
                'building' => '渋谷タワー',
                'payment_method' => 'コンビニ払い',
            ]);

        // 4. 購入後に商品一覧ページへリダイレクトされることを確認
        $response->assertRedirect(route('items.index'));

        // 5. purchasesテーブルに購入記録が正しく保存されていることを検証
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
        // 1. ユーザーを1件作成（ログイン用）
        $user = User::factory()->create()->first();

        // 2. 未売却状態の商品を1件作成
        $item = Item::factory()->create(['sold' => false]);

        // 3. ログインユーザーで購入処理を実行（POST送信）
        $this->actingAs($user)
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',
                'address' => '東京都渋谷区',
                'building' => '渋谷タワー',
                'payment_method' => 'コンビニ払い',
            ]);

        // 4. 購入後の商品一覧ページにアクセスし、
        //    「Sold」ラベルが画面に表示されていることを検証
        $this->get(route('items.index'))
            ->assertSee('Sold');
    }

    /**
     * ✅ 3. 購入後、プロフィールの購入履歴に追加されるか確認するテスト
     */
    public function test_purchase_history_is_added_after_trade_completed()
    {
        // 1. 購入者ユーザーを作成
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create();

        // 2. 出品者ユーザーを作成
        /** @var \App\Models\User $seller */
        $seller = User::factory()->create();

        // 3. 出品者が所有する未売却商品を作成
        $item = Item::factory()->create([
            'sold' => false,
            'user_id' => $seller->id,
            'name' => 'テスト商品A',
        ]);

        // 4. 購入者ユーザーとして購入処理をPOST送信
        $response = $this->actingAs($buyer)->post(route('purchase.store', ['item_id' => $item->id]), [
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
            'building' => 'テストビル202',
            'payment_method' => 'コンビニ払い',
        ]);

        // 5. 購入完了後、商品一覧ページへリダイレクトされることを確認
        $response->assertRedirect(route('items.index'));

        // 6. 購入によるTradeレコードが作成されているか確認
        $trade = \App\Models\Trade::where('item_id', $item->id)->first();
        $this->assertNotNull($trade, 'Tradeレコードが作成されていること');

        // 7. 購入者による評価送信（スコア5）POSTを送信
        $responseBuyerEval = $this->actingAs($buyer)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);

        // 8. 評価送信後、取引チャット画面へリダイレクトされることを検証
        $responseBuyerEval->assertRedirect(route('trade.chat.show', $trade->id));

        // 9. 出品者による評価送信（スコア5）POSTを送信
        $responseSellerEval = $this->actingAs($seller)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);

        // 10. 同様に取引チャット画面へリダイレクトされることを検証
        $responseSellerEval->assertRedirect(route('trade.chat.show', $trade->id));

        // 11. 購入者ユーザーで取引チャット画面にアクセス（状態更新のため）
        $this->actingAs($buyer)->get(route('trade.chat.show', $trade->id));

        // 12. Tradeレコードを最新情報でリフレッシュし、
        //     is_completedがtrue（取引完了）となっているか検証
        $trade->refresh();
        $this->assertTrue((bool)$trade->is_completed, 'Tradeのis_completedがtrueになっていること');

        // 13. 購入者の購入履歴ページにアクセスし、
        //     購入商品名が画面に表示されていることを確認
        $responseBuyList = $this->actingAs($buyer)->get(route('user.show', ['page' => 'buy']));
        $responseBuyList->assertSee('テスト商品A');
    }
}
