<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
    public function test_purchase_history_is_added_after_purchase()
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

        // 5. ユーザーの購入履歴ページにアクセスし、購入した商品名が表示されていることを確認
        $this->get(route('user.show', ['page' => 'buy']))
            ->assertSee($item->name);  // 購入した商品の名前が表示されているか確認
    }
}
