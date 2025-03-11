<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Facades\Log;

class ProfileTest extends TestCase
{
    use RefreshDatabase; // テストごとにデータベースをリセットする

    /** @test */
    public function 購入した商品がクリックされたときに表示される()
    {
        // 1. ユーザーを作成する（ログインに必要）
        $user = User::factory()->create([
            'profile_image' => 'path/to/profile_image.jpg', // ここで画像のパスを設定
        ])->first();

        // 2. 出品した商品を作成する（ユーザーが出品した商品一覧に必要）
        $item1 = Item::factory()->create(['user_id' => $user->id]);
        $item2 = Item::factory()->create(['user_id' => $user->id]);

        // 3. 購入した商品を作成する（ユーザーが購入した商品一覧に必要）
        $purchasedItem = Item::factory()->create();
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // 4. ユーザーとしてログインする
        $this->actingAs($user);

        // 5. プロフィールページを開く（最初は購入した商品は非表示）
        $response = $this->get(route('user.show'));

        // プロフィール画像のパスをログに記録
        Log::info('プロフィール画像のパス:', ['profile_image' => $user->profile_image]);

        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->profile_image) // プロフィール画像が見えることを確認
            ->assertSee($item1->name)
            ->assertSee($item2->name)
            ->assertDontSee($purchasedItem->name); // 最初は購入商品が見えない

        // 6. 購入した商品のページをクリックする
        $response = $this->get(route('user.buyList'));

        // 7. 購入商品が表示されることを確認する
        $response->assertStatus(200)
            ->assertSee($purchasedItem->name);
    }
}
