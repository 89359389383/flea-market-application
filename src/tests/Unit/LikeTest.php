<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーがいいねを追加できる()
    {
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        $this->actingAs($user);

        // 商品詳細ページを開いていいね数を確認（初期値0）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertStatus(200);
        $response->assertSeeText('0'); // 初期いいね数

        // いいねを押す
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // データベース確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 商品詳細ページでいいね数が1に増えていることを確認
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('1');

        // リダイレクト確認
        $response->assertStatus(200);
    }

    /** @test */
    public function いいね済みのアイテムのアイコンが変化する()
    {
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        $this->actingAs($user);

        // 商品詳細ページを開く
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // いいねを押す
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // 商品詳細ページを再取得してアイコンの変化確認（CSSクラスに "liked" が含まれているか）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSee('like-button liked'); // フロントでの「色が変わった」＝クラス名の切り替え
    }

    /** @test */
    public function いいねを解除できる()
    {
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        $this->actingAs($user);

        // 商品詳細ページ確認（初期0）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('0');

        // いいね追加
        $this->post(route('items.toggleLike', ['id' => $item->id]));
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 商品詳細ページ確認（1）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('1');

        // いいね解除
        $this->post(route('items.toggleLike', ['id' => $item->id]));
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 商品詳細ページ確認（0に戻っているか）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('0');
    }
}
