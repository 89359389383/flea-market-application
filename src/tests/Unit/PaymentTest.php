<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. 「コンビニ払い」を選択したときに、サイドバーに正しく反映されることを確認するテスト
     */
    public function it_displays_convenience_store_payment_in_sidebar()
    {
        // 1. ユーザーを作成
        User::factory()->create([
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市',
            'building' => 'テストビル202',
        ]);
        $user = User::first();

        // 2. 商品を作成
        $item = Item::factory()->create(['price' => 3000]);

        // 3. コンビニ払いを選択して購入ページにアクセス
        $response = $this->actingAs($user)->get(route('purchase.show', [
            'item_id' => $item->id,
            'payment_method' => 'コンビニ払い',
        ]));

        // 4. レスポンスが正常であることを確認
        $response->assertStatus(200);

        // 5. HTML全体を取得し、空白・改行を削除して比較用に整形
        $html = preg_replace('/\s+/', '', $response->getContent());
        $expected = '<divclass="summary-value"id="selected-payment-method">コンビニ払い</div>';

        // 6. サイドバー内に「コンビニ払い」が表示されていることを確認
        $this->assertStringContainsString($expected, $html);
    }

    /**
     * ✅ 2. 「カード払い」を選択したときに、サイドバーに正しく反映されることを確認するテスト
     */
    public function it_displays_card_payment_in_sidebar()
    {
        // 1. ユーザーを作成
        User::factory()->create([
            'postal_code' => '987-6543',
            'address' => '大阪府大阪市',
            'building' => 'テストビル202',
        ]);
        $user = User::first();

        // 2. 商品を作成
        $item = Item::factory()->create(['price' => 10000]);

        // 3. カード払いを選択して購入ページにアクセス
        $response = $this->actingAs($user)->get(route('purchase.show', [
            'item_id' => $item->id,
            'payment_method' => 'カード払い',
        ]));

        // 4. レスポンスが正常であることを確認
        $response->assertStatus(200);

        // 5. HTML全体を取得し、空白・改行を削除して比較用に整形
        $html = preg_replace('/\s+/', '', $response->getContent());
        $expected = '<divclass="summary-value"id="selected-payment-method">カード払い</div>';

        // 6. サイドバー内に「カード払い」が表示されていることを確認
        $this->assertStringContainsString($expected, $html);
    }
}
