<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class PurchaseTest extends TestCase
{
    // テストごとにデータベースをリフレッシュ（初期化）するトレイトを使用
    use RefreshDatabase;

    /**
     * ✅ 1. ログインユーザーが商品を購入できるか確認するテスト
     */
    public function test_logged_in_user_can_purchase_item()
    {
        // 1. テスト用ユーザーを作成
        $user = User::factory()->create()->first();
        // 2. 商品が売れていない状態で商品を作成
        $item = Item::factory()->create(['sold' => false]);

        // 3. ユーザーがログインした状態で購入処理を実行
        $response = $this->actingAs($user)
            // 4. 購入用のルートに対してPOSTリクエストを送信
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',  // 郵便番号
                'address' => '東京都渋谷区',    // 住所
                'building' => '渋谷タワー',    // 建物名
                'payment_method' => 'コンビニ払い', // 支払い方法
            ]);

        // 5. 購入後、アイテム一覧ページにリダイレクトされることを確認
        $response->assertRedirect(route('items.index'));
        // 6. データベースに購入記録が追加されたことを確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,    // 購入したユーザーのID
            'item_id' => $item->id,    // 購入した商品のID
        ]);
    }

    /**
     * ✅ 2. 購入後に商品一覧に「Sold」と表示されるか確認するテスト
     */
    public function test_sold_label_appears_after_purchase()
    {
        // 1. テスト用ユーザーを作成
        $user = User::factory()->create()->first();
        // 2. 商品が売れていない状態で商品を作成
        $item = Item::factory()->create(['sold' => false]);

        // 3. ユーザーがログインした状態で購入処理を実行
        $this->actingAs($user)
            // 4. 購入用のルートに対してPOSTリクエストを送信
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',  // 郵便番号
                'address' => '東京都渋谷区',    // 住所
                'building' => '渋谷タワー',    // 建物名
                'payment_method' => 'コンビニ払い', // 支払い方法
            ]);

        // 5. アイテム一覧ページを取得し、「Sold」と表示されていることを確認
        $this->get(route('items.index'))
            ->assertSee('Sold');
    }

    /**
     * ✅ 3. 購入後、プロフィールの購入履歴に追加されるか確認するテスト
     */
    public function test_purchase_history_is_added_after_trade_completed()
    {
        Log::debug('テスト開始: test_purchase_history_is_added_after_trade_completed');

        // 1. ユーザー作成
        $buyer = User::factory()->create();
        Log::debug('buyer作成完了', ['buyer_id' => $buyer->id]);

        $seller = User::factory()->create();
        Log::debug('seller作成完了', ['seller_id' => $seller->id]);

        // 2. 商品作成
        $item = Item::factory()->create([
            'sold' => false,
            'user_id' => $seller->id,
            'name' => 'テスト商品A',
        ]);
        Log::debug('item作成完了', ['item_id' => $item->id, 'item_name' => $item->name]);

        // 3. 購入処理POST
        $response = $this->actingAs($buyer)->post(route('purchase.store', ['item_id' => $item->id]), [
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
            'building' => 'テストビル202',
            'payment_method' => 'コンビニ払い',
        ]);
        Log::debug('購入処理POST送信完了', ['response_status' => $response->getStatusCode()]);
        $response->assertRedirect(route('items.index'));

        // 4. 取引レコード取得
        $trade = \App\Models\Trade::where('item_id', $item->id)->first();
        $this->assertNotNull($trade, 'Tradeレコードが作成されていること');
        Log::debug('Tradeレコード取得', ['trade_id' => $trade->id, 'item_id' => $trade->item_id]);

        // 5. 購入者が評価投稿
        $responseBuyerEval = $this->actingAs($buyer)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        Log::debug('購入者評価POST送信完了', ['status' => $responseBuyerEval->getStatusCode()]);
        $responseBuyerEval->assertRedirect(route('trade.chat.show', $trade->id));

        // 6. 出品者が評価投稿
        $responseSellerEval = $this->actingAs($seller)->post(route('trade.evaluate.store', ['trade_id' => $trade->id]), ['score' => 5]);
        Log::debug('出品者評価POST送信完了', ['status' => $responseSellerEval->getStatusCode()]);
        $responseSellerEval->assertRedirect(route('trade.chat.show', $trade->id));

        // === ここが絶対必要！ ===
        // チャット画面GETでis_completedフラグが立つ
        $this->actingAs($buyer)->get(route('trade.chat.show', $trade->id));
        $trade->refresh();
        $this->assertTrue((bool)$trade->is_completed, 'Tradeのis_completedがtrueになっていること');
        Log::debug('Trade完了状態確認', ['is_completed' => $trade->is_completed]);

        // 8. 購入履歴一覧取得ページへGET
        $responseBuyList = $this->actingAs($buyer)->get(route('user.show', ['page' => 'buy']));
        Log::debug('購入履歴一覧ページGET送信完了', ['status' => $responseBuyList->getStatusCode()]);

        // 9. 商品名が画面に表示されているかアサーション
        $responseBuyList->assertSee('テスト商品A');
        Log::debug('購入履歴一覧に商品名表示確認OK');

        Log::debug('テスト終了: test_purchase_history_is_added_after_trade_completed');
    }
}
