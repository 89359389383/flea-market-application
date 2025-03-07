<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\Item;

class PaymentTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * 支払い方法の選択がサーバー側で反映されるかをテスト
     */
    public function testPaymentMethodIsReflectedImmediately()
    {
        // ① ダミーユーザーを作成
        $user = User::factory()->create()->first();
        ([
            'password' => bcrypt('password123'),
        ]);

        // ② 購入対象の商品を作成
        $item = Item::factory()->create();

        // ③ ユーザーがログインページでログインする
        $loginResponse = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123', // 正しいパスワード
        ]);

        // ④ ログイン後、トップページへリダイレクト
        $loginResponse->assertRedirect(route('items.index'));

        // ⑤ 商品購入ページにアクセス
        $purchasePageResponse = $this->actingAs($user)->get(route('purchase.show', ['item_id' => $item->id]));
        $purchasePageResponse->assertStatus(200);

        // ⑥ 支払い方法を選択して「購入する」ボタンを押す
        $purchaseResponse = $this->actingAs($user)->post(route('purchase.store', ['item_id' => $item->id]), [
            'payment_method' => 'コンビニ払い',
        ]);

        // ⑦ 期待通りに購入ページにリダイレクトするか確認
        $purchaseResponse->assertRedirect(route('purchase.show', ['item_id' => $item->id]));

        // ⑧ JavaScript の処理に依存せず、レスポンス HTML に「コンビニ払い」の文字が含まれるか確認
        $updatedPageResponse = $this->actingAs($user)->get(route('purchase.show', ['item_id' => $item->id]));
        $updatedPageResponse->assertSee('コンビニ払い');
    }
}
