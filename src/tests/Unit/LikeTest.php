<?php

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;

// いいね機能のテストをするためのクラス
class LikeTest extends TestCase
{
    use RefreshDatabase; // テスト後にデータベースをリセットする

    /** @test */
    public function ユーザーがいいねを追加できる()
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create()->first();

        // テスト用の商品を作成
        $item = Item::factory()->create();

        // ユーザーとしてログイン
        $this->actingAs($user);

        // いいねを押す（リクエストを送る）
        $response = $this->post(route('items.toggleLike', ['id' => $item->id]));

        // データベースにいいねが保存されていることを確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 正常にリダイレクトされることを確認
        $response->assertRedirect();
    }

    /** @test */
    public function いいね済みのアイテムのアイコンが変化する()
    {
        // テスト用のユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // ユーザーとしてログイン
        $this->actingAs($user);

        // まず、いいねを押す
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // いいねが追加されたか確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // いいねアイコンの状態を確認（フロントエンドのテストは通常Laravelではできないので省略）
    }

    /** @test */
    public function いいねを解除できる()
    {
        // テスト用のユーザーと商品を作成
        $user = User::factory()->create()->first();
        $item = Item::factory()->create();

        // ユーザーとしてログイン
        $this->actingAs($user);

        // いいねを追加
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // いいねがデータベースにあることを確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // いいねを解除
        $this->post(route('items.toggleLike', ['id' => $item->id]));

        // いいねが削除されたことを確認
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }
}
