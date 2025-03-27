<?php

use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_address_is_reflected_on_purchase_page()
    {
        Log::info('テスト開始: 登録した住所が商品購入画面に正しく反映されるか');

        // ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // ログイン状態にする
        $this->actingAs($user);

        // 送付先住所変更画面（GET）
        $this->get(route('address.edit', ['item_id' => $item->id]));

        // 送付先住所登録処理（POST）
        $response = $this->post(route('address.update', ['item_id' => $item->id]), [
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト町1-2-3',
            'building' => 'テストビル101',
        ]);

        // 登録後のリダイレクト先が正しいか確認（購入画面へ）
        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // 最新のユーザー情報を取得（DBの状態を確認）
        $user->refresh();
        $this->assertEquals('123-4567', $user->postal_code);

        // 商品購入画面にアクセス
        $response = $this->get(route('purchase.show', ['item_id' => $item->id]));

        // 画面に住所が反映されているか確認
        $response->assertSee('123-4567');
        $response->assertSee('東京都新宿区テスト町1-2-3');
        $response->assertSee('テストビル101');

        Log::info('テスト終了: 登録した住所が商品購入画面に正しく反映されるか');
    }

    public function test_purchased_item_has_correct_address()
    {
        Log::info('テスト開始: 購入した商品に送付先住所が正しく紐づいて登録されるか');

        // ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        $this->actingAs($user);

        // 住所変更画面にアクセス（GET）
        $this->get(route('address.edit', ['item_id' => $item->id]));

        // 住所登録処理（POST）
        $response = $this->post(route('address.update', ['item_id' => $item->id]), [
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
        ]);
        $response->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // 最新ユーザー情報を取得してログ出力
        $user->refresh();
        Log::info('住所登録完了', [
            'postal_code' => $user->postal_code,
            'address' => $user->address,
            'building' => $user->building,
        ]);

        // 購入処理実行（POST）
        $response = $this->post(route('purchase.store', ['item_id' => $item->id]), [
            'postal_code' => $user->postal_code,
            'address' => $user->address,
            'building' => $user->building,
            'payment_method' => 'コンビニ払い',
        ]);

        // 購入データがデータベースに存在することを確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
        ]);

        Log::info('テスト終了: 購入した商品に送付先住所が正しく紐づいて登録されていることを確認');
    }
}
