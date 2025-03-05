<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Comment;

class CommentTest extends TestCase
{
    use RefreshDatabase; // データベースをリセットする（テストごとに初期化）

    /**
     * ログインしたユーザーはコメントを投稿できるかテストする
     */
    public function test_authenticated_user_can_post_comment()
    {
        // ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user); // ユーザーをログイン状態にする

        // 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // コメントのデータを作成（フォームから送信される想定）
        $commentData = [
            'comment' => 'これはテストコメントです！',
        ];

        // コメントを送信（POSTリクエスト）
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), $commentData);

        // コメントがデータベースに保存されたか確認
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment' => $commentData['comment'],
        ]);

        // コメント数が1つ増えたことを確認
        $this->assertEquals(1, Comment::count());

        // コメント投稿後、商品詳細ページへリダイレクトするか確認
        $response->assertRedirect(route('items.show', $item->id));
    }

    /**
     * 入力なしでコメントを投稿した場合にバリデーションエラーが出るかテストする
     */
    public function test_comment_cannot_be_empty()
    {
        // ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // 空のコメントを送信（バリデーションに引っかかる）
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), [
            'comment' => '',
        ]);

        // コメントが保存されていないことを確認
        $this->assertEquals(0, Comment::count());

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['comment' => 'コメントを入力してください']);
    }

    /**
     * 256文字以上のコメントを投稿した場合にバリデーションエラーが出るかテストする
     */
    public function test_comment_cannot_exceed_255_characters()
    {
        // ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 商品を作成する（コメント対象）
        $item = Item::factory()->create();

        // 256文字のコメントを作成
        $longComment = str_repeat('あ', 256); // 256文字の「あ」

        // コメントを送信
        $response = $this->post(route('items.comment.store', ['item_id' => $item->id]), [
            'comment' => $longComment,
        ]);

        // コメントが保存されていないことを確認
        $this->assertEquals(0, Comment::count());

        // エラーメッセージが表示されることを確認
        $response->assertSessionHasErrors(['comment' => 'コメントは255文字以内で入力してください']);
    }
}
