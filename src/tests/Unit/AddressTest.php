<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. 登録した住所が商品購入画面に正しく反映されるか確認するテスト
     */
    public function test_registered_address_is_reflected_on_purchase_page()
    {
        // 1. ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // 2. ログイン状態にする
        $this->actingAs($user);

        // 3. 送付先住所変更画面にアクセス
        $this->get(route('address.edit', ['item_id' => $item->id]));

        // 4. 送付先住所を登録
        $response = $this->post(route('address.update', ['item_id' => $item->id]), [
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト町1-2-3',
            'building' => 'テストビル101',
        ]);

        // 5. 登録後のリダイレクト先が正しいか確認
        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // 6. データベースに住所が正しく保存されているか確認
        $user->refresh();
        $this->assertEquals('123-4567', $user->postal_code);

        // 7. 商品購入画面にアクセス
        $response = $this->get(route('purchase.show', ['item_id' => $item->id]));

        // 8. 画面に住所が反映されているか確認
        $response->assertSee('123-4567');
        $response->assertSee('東京都新宿区テスト町1-2-3');
        $response->assertSee('テストビル101');
    }

    /**
     * ✅ 2. 購入した商品に送付先住所が正しく紐づいて登録されるか確認するテスト
     */
    public function test_purchased_item_has_correct_address()
    {
        // 1. ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // 2. ログイン状態にする
        $this->actingAs($user);

        // 3. 住所変更画面にアクセス
        $this->get(route('address.edit', ['item_id' => $item->id]));

        // 4. 住所を登録
        $response = $this->post(route('address.update', ['item_id' => $item->id]), [
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
        ]);
        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // 5. 最新ユーザー情報を取得
        $user->refresh();

        // 6. 購入処理を実行
        $response = $this->post(route('purchase.store', ['item_id' => $item->id]), [
            'postal_code' => $user->postal_code,
            'address' => $user->address,
            'building' => $user->building,
            'payment_method' => 'コンビニ払い',
        ]);

        // 7. 購入データがデータベースに正しく保存されているか確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
        ]);
    }
}
