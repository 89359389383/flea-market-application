<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. ユーザーが商品にいいねを追加できることを確認するテスト
     */
    public function test_user_can_add_like_to_item()
    {
        // 1. ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // 2. ユーザーとしてログイン
        $this->actingAs($user);

        // 3. 商品詳細ページを開いていいね数を確認（初期値0）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertStatus(200);
        $response->assertSeeText('0'); // 初期いいね数

        // 4. いいねを押す
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // 5. データベースにいいねが保存されているか確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 6. 商品詳細ページでいいね数が1に増えていることを確認
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('1');

        // 7. リダイレクト確認
        $response->assertStatus(200);
    }

    /**
     * ✅ 2. いいね済みのアイテムのアイコンが変化することを確認するテスト
     */
    public function test_like_icon_changes_when_item_is_liked()
    {
        // 1. ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // 2. ユーザーとしてログイン
        $this->actingAs($user);

        // 3. 商品詳細ページを開く
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // 4. いいねを押す
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // 5. 商品詳細ページを再取得してアイコンの変化確認（CSSクラスに "liked" が含まれているか）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSee('like-button liked'); // フロントでの「色が変わった」＝クラス名の切り替え
    }

    /**
     * ✅ 3. ユーザーがいいねを解除できることを確認するテスト
     */
    public function test_user_can_remove_like_from_item()
    {
        // 1. ユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // 2. ユーザーとしてログイン
        $this->actingAs($user);

        // 3. 商品詳細ページ確認（初期0）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('0');

        // 4. いいね追加
        $this->post(route('items.toggleLike', ['id' => $item->id]));
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 5. 商品詳細ページ確認（1）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('1');

        // 6. いいね解除
        $this->post(route('items.toggleLike', ['id' => $item->id]));
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 7. 商品詳細ページ確認（0に戻っているか）
        $response = $this->get(route('items.show', ['item_id' => $item->id]));
        $response->assertSeeText('0');
    }
}
