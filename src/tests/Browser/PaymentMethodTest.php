<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Item;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PaymentMethodTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * 支払い方法を選択すると、右側の表示が即時反映されることをテスト
     */
    public function testPaymentMethodIsReflectedImmediately()
    {
        $this->browse(function (Browser $browser) {
            // テスト用のユーザーと商品を作成
            $user = User::factory()->create([
                'password' => bcrypt('password'), // パスワードは平文でログインに使うため
            ]);
            $item = Item::factory()->create();

            // ログイン → 商品購入画面に遷移
            $browser->visit(route('login'))
                ->type('email', $user->email)
                ->type('password', 'password')
                ->press('ログイン') // ログインボタンのラベルに合わせて修正必要
                ->assertPathIs(route('items.index', [], false)) // 相対パスで比較
                ->visit(route('purchase.show', ['item_id' => $item->id]))
                ->assertSee('選択してください')

                // 「カード払い」を選択
                ->select('#payment-method', 'カード払い')
                ->pause(500) // DOM更新を待つ

                // 右側の表示に「カード払い」が即時反映されていることを確認
                ->assertSeeIn('#selected-payment-method', 'カード払い');
        });
    }
}
