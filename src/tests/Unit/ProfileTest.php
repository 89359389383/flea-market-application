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
        $user = User::factory()->create([
            'profile_image' => 'path/to/profile_image.jpg',
        ])->first();

        $item1 = Item::factory()->create(['user_id' => $user->id]);
        $item2 = Item::factory()->create(['user_id' => $user->id]);

        $purchasedItem = Item::factory()->create();
        Purchase::factory()->create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('user.show'));

        Log::info('プロフィール画像のパス:', ['profile_image' => $user->profile_image]);

        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->profile_image)
            ->assertSee($item1->name)
            ->assertSee($item2->name)
            ->assertDontSee($purchasedItem->name);

        $response = $this->get(route('user.show', ['page' => 'buy']));

        $response->assertStatus(200)
            ->assertSee($purchasedItem->name);
    }

    /** @test */
    public function 出品した商品がプロフィールページで表示される()
    {
        // ユーザーを作成
        $user = User::factory()->create([
            'profile_image' => 'path/to/profile_image.jpg',
        ])->first();

        // 出品した商品を2件作成
        $item1 = Item::factory()->create(['user_id' => $user->id, 'name' => '出品商品A']);
        $item2 = Item::factory()->create(['user_id' => $user->id, 'name' => '出品商品B']);

        // 他人が出品した商品（表示されないはず）
        $otherUser = User::factory()->create();
        $otherItem = Item::factory()->create(['user_id' => $otherUser->id, 'name' => '他人の商品']);

        // ログイン
        $this->actingAs($user);

        // プロフィールページ（出品商品タブ）へアクセス
        $response = $this->get(route('user.show', ['page' => 'sell']));

        // ステータスコード確認と表示確認
        $response->assertStatus(200)
            ->assertSee('出品商品A')
            ->assertSee('出品商品B')
            ->assertDontSee('他人の商品'); // 他人の商品は表示されない
    }
}
