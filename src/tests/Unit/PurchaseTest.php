<?php

namespace Tests\Feature;

// 必要なモデル（User, Item, Purchase）をインポート
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
// データベースをリフレッシュしてテストを行うための機能をインポート
use Illuminate\Foundation\Testing\RefreshDatabase;
// テストケースの基底クラスをインポート
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    // テストごとにデータベースをリフレッシュ（初期化）するトレイトを使用
    use RefreshDatabase;

    /** @test */
    // ログインしたユーザーが商品を購入できるかをテスト
    public function ログインユーザーが商品を購入できる()
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create()->first();
        // 商品が売れていない状態で商品を作成
        $item = Item::factory()->create(['sold' => false]);

        // ユーザーがログインした状態で購入処理を実行
        $response = $this->actingAs($user)
            // 購入用のルートに対してPOSTリクエストを送信
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',  // 郵便番号
                'address' => '東京都渋谷区',    // 住所
                'building' => '渋谷タワー',    // 建物名
                'payment_method' => 'カード払い', // 支払い方法
            ]);

        // 購入後、アイテム一覧ページにリダイレクトされることを確認
        $response->assertRedirect(route('items.index'));
        // データベースに購入記録が追加されたことを確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,    // 購入したユーザーのID
            'item_id' => $item->id,    // 購入した商品のID
        ]);
    }

    /** @test */
    // 購入後に商品一覧に「Sold」と表示されるかをテスト
    public function 購入後_商品一覧に_Sold_と表示される()
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create()->first();
        // 商品が売れていない状態で商品を作成
        $item = Item::factory()->create(['sold' => false]);

        // ユーザーがログインした状態で購入処理を実行
        $this->actingAs($user)
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',  // 郵便番号
                'address' => '東京都渋谷区',    // 住所
                'building' => '渋谷タワー',    // 建物名
                'payment_method' => 'カード払い', // 支払い方法
            ]);

        // アイテム一覧ページを取得し、「Sold」と表示されていることを確認
        $this->get(route('items.index'))
            ->assertSee('Sold');
    }

    /** @test */
    // 購入後、プロフィールの購入履歴に追加されるかをテスト
    public function 購入後_プロフィールの購入履歴に追加される()
    {
        // テスト用ユーザーを作成
        $user = User::factory()->create()->first();
        // 商品が売れていない状態で商品を作成
        $item = Item::factory()->create(['sold' => false]);

        // ユーザーがログインした状態で購入処理を実行
        $this->actingAs($user)
            ->post(route('purchase.store', ['item_id' => $item->id]), [
                'postal_code' => '123-4567',  // 郵便番号
                'address' => '東京都渋谷区',    // 住所
                'building' => '渋谷タワー',    // 建物名
                'payment_method' => 'カード払い', // 支払い方法
            ]);

        // ユーザーの購入履歴ページにアクセスし、購入した商品名が表示されていることを確認
        $this->get(route('user.buyList'))
            ->assertSee($item->name);  // 購入した商品の名前が表示されているか確認
    }
}
