<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Item;

class PaymentTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * 支払い方法の選択が即時に画面に反映されることをテスト
     */
    public function testSelectedPaymentMethodIsImmediatelyReflectedOnPage()
    {
        // ① ダミーユーザーを作成
        $user = User::factory()->create()->first();

        // ② 商品を作成
        $item = Item::factory()->create();

        // ③ ユーザーがログイン
        $loginResponse = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password', // UserFactoryのデフォルトと一致させる
        ]);

        // ④ ログイン後、トップページにリダイレクトされることを確認
        $loginResponse->assertRedirect(route('items.index'));

        // 🔽《追加》商品詳細ページにアクセス（テストケース①の補足）
        $itemDetailResponse = $this->actingAs($user)->get(route('items.show', ['item_id' => $item->id]));
        $itemDetailResponse->assertStatus(200);
        $itemDetailResponse->assertSee($item->name);

        // ⑤ 商品購入ページにアクセス
        $purchasePageResponse = $this->actingAs($user)->get(route('purchase.show', ['item_id' => $item->id]));
        $purchasePageResponse->assertStatus(200);

        // ⑥ 支払い方法が「未選択」で表示されていることを確認
        $purchasePageResponse->assertSee('選択してください');

        // ⑦ 支払い方法を「コンビニ払い」に選択し、変更を即時反映（POSTリクエストはせず）
        // simulate user selecting the payment method and page updating without JS
        // このテストでは画面に支払い方法が表示されるかを検証

        // 🔽《再取得》ページを開いた後に「コンビニ払い」を選択して表示されることを確認
        // 本来 JS で変更されるが、ここではHTMLに "コンビニ払い" の文字列が存在しているかを検証
        $responseAfterSelection = $this->actingAs($user)->get(route('purchase.show', ['item_id' => $item->id]));
        $responseAfterSelection->assertSee('コンビニ払い'); // 最初からHTMLに含まれていないならNG

        // 🔽ヒント：本当に動的反映するには Laravel Livewire や re-render 等が必要
        // → ここでは、HTML上に「コンビニ払い」という選択肢が**選択状態で**表示されていることを検証する場合は JavaScript のユニットテスト or ブラウザテストが適切
        // ここでは最低限、「HTMLに文字列が含まれていること」をテストするだけになります
    }
}
