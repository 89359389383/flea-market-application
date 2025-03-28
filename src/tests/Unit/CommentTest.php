<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Comment;

class CommentTest extends TestCase
{
    use RefreshDatabase; // データベースをリセットする（テストごとに初期化）

    /**
     * ✅ 1. ログインしたユーザーはコメントを投稿できるか確認するテスト
     */
    public function test_authenticated_user_can_post_comment()
    {
        // 1. ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user); // ユーザーをログイン状態にする

        // 2. 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // 3. コメントのデータを作成（フォームから送信される想定）
        $commentData = [
            'comment' => 'これはテストコメントです！',
        ];

        // 4. コメントを送信（POSTリクエスト）
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), $commentData);

        // 5. コメントがデータベースに保存されたか確認
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment' => $commentData['comment'],
        ]);

        // 6. コメント数が1つ増えたことを確認
        $this->assertEquals(1, Comment::count());

        // 7. コメント投稿後、商品詳細ページへリダイレクトするか確認
        $response->assertRedirect(route('items.show', $item->id));
    }

    /**
     * ✅ 2. 空のコメントを投稿した場合のバリデーションエラーを確認するテスト
     */
    public function test_comment_cannot_be_empty()
    {
        // 1. ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 2. 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // 3. 空のコメントを送信（バリデーションに引っかかる）
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), [
            'comment' => '',
        ]);

        // 4. コメントが保存されていないことを確認
        $this->assertEquals(0, Comment::count());

        // 5. エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['comment' => 'コメントを入力してください']);
    }

    /**
     * ✅ 3. 256文字以上のコメントを投稿した場合のバリデーションエラーを確認するテスト
     */
    public function test_comment_cannot_exceed_255_characters()
    {
        // 1. ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 2. 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // 3. 256文字のコメントを作成
        $longComment = str_repeat('あ', 256); // 256文字の「あ」

        // 4. コメントを送信
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), [
            'comment' => $longComment,
        ]);

        // 5. コメントが保存されていないことを確認
        $this->assertEquals(0, Comment::count());

        // 6. エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['comment' => 'コメントは255文字以内で入力してください']);
    }

    /**
     * ✅ 4. ログインしていないユーザーはコメントを投稿できないか確認するテスト
     */
    public function test_guest_cannot_post_comment()
    {
        // 1. 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // 2. コメントのデータを作成（フォームから送信される想定）
        $commentData = [
            'comment' => 'これはゲストユーザーのテストコメントです！',
        ];

        // 3. ゲストユーザーとしてコメントを送信（POSTリクエスト）
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), $commentData);

        // 4. コメントがデータベースに保存されていないことを確認
        $this->assertEquals(0, Comment::count());

        // 5. ログインページへリダイレクトされることを確認
        $response->assertRedirect(route('login'));
    }
}
