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

    /**
     * 【テストケース1】登録した住所が商品購入画面に正しく反映されるか
     */
    public function test_registered_address_is_reflected_on_purchase_page()
    {
        Log::info('テスト開始: 登録した住所が商品購入画面に正しく反映されるか');

        // 1. ユーザーを作成（データベースに仮のユーザー情報を登録）
        $user = User::factory()->create([
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト町1-2-3',
            'building' => 'テストビル101',
        ]);

        Log::info('ユーザー作成完了', ['user_id' => $user->id, 'postal_code' => $user->postal_code]);

        // 2. 商品を作成（テスト用の商品データを作成）
        $item = Item::factory()->create();
        Log::info('商品作成完了', ['item_id' => $item->id]);

        // 3. 作成したユーザーでログイン
        $this->actingAs($user);

        // 4. 商品購入ページにアクセス（購入ページの情報が正しく表示されるか確認）
        $response = $this->get(route('purchase.show', ['item_id' => $item->id]));
        Log::info('商品購入ページアクセス', ['response' => $response->getContent()]);

        // 5. ページに登録した住所が正しく表示されているか確認
        $response->assertSee('123-4567');
        $response->assertSee('東京都新宿区テスト町1-2-3');
        $response->assertSee('テストビル101');

        Log::info('テスト終了: 登録した住所が商品購入画面に正しく反映されるか');
    }

    /**
     * 【テストケース2】購入した商品に送付先住所が正しく紐づいて登録されるか
     */
    public function test_purchased_item_has_correct_address()
    {
        Log::info('テスト開始: 購入した商品に送付先住所が正しく紐づいて登録されるか');

        // 1. ユーザーを作成
        $user = User::factory()->create([
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
        ]);
        Log::info('ユーザー作成完了', ['user_id' => $user->id, 'postal_code' => $user->postal_code]);

        // 2. 商品を作成
        $item = Item::factory()->create();
        Log::info('商品作成完了', ['item_id' => $item->id]);

        // 3. ユーザーでログイン
        $this->actingAs($user);

        // 4. 商品を購入する（テストデータを使って購入処理を実行）
        $response = $this->post(route('purchase.store', ['item_id' => $item->id]), [
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
            'payment_method' => 'カード払い',
        ]);
        Log::info('商品購入リクエスト送信', ['response' => $response->getContent()]);

        // 5. 購入データがデータベースに保存されているか確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市テスト区4-5-6',
            'building' => 'テストマンション202',
        ]);

        Log::info('購入データがデータベースに保存されていることを確認');

        Log::info('テスト終了: 購入した商品に送付先住所が正しく紐づいて登録されるか');
    }
}
