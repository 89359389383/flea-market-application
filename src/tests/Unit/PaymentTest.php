<?php

namespace Tests\Unit;

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
    public function test_displays_convenience_store_payment_in_sidebar()
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

        // 5. HTML全体を取得して、空白や改行をすべて削除（比較しやすくするため）
        // たとえば <div class="abc"> → <divclass="abc"> に変わる
        // HTMLは人間が見やすいように空白が入ってるけど、それだと文字を正確に比べづらいので、まずは全部くっつける！
        $html = preg_replace('/\s+/', '', $response->getContent());

        // 6. 比較用の「正解の文字列」を用意
        // 「サイドバーにコンビニ払いが表示されているはずだよね？」という見本
        // 上のHTMLと同じように空白をなくした形で書いてある
        $expected = '<divclass="summary-value"id="selected-payment-method">コンビニ払い</div>';

        // 7. 実際のHTMLの中に、上の「正解の文字列」がちゃんと含まれているかチェック
        // もし見つからなければ「バグかも」としてテストが失敗する
        $this->assertStringContainsString($expected, $html);
    }

    /**
     * ✅ 2. 「カード払い」を選択したときに、サイドバーに正しく反映されることを確認するテスト
     */
    public function test_displays_card_payment_in_sidebar()
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
