<?php

use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Factories\CommentFactory;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 商品詳細ページに必要な情報が表示される()
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $item = Item::factory()->create([
            'user_id' => $user->id,
            'name' => 'テスト商品',
            'brand_name' => 'テストブランド',
            'price' => 5000,
            'description' => 'これはテスト用の商品です',
            'condition' => '良好',
            'likes_count' => 10,
            'comments_count' => 1,
            'image' => 'test_image.jpg',
        ]);

        $categories = Category::factory()->count(2)->create();
        $item->categories()->attach($categories->pluck('id'));

        // コメントとコメントしたユーザー情報を追加
        $commentUser = User::factory()->create(['name' => 'コメントユーザー']);
        $comment = Comment::factory()->create([
            'item_id' => $item->id,
            'user_id' => $commentUser->id,
            'comment' => 'これはテストコメントです。',
        ]);

        $response = $this->get(route('items.show', ['item_id' => $item->id]));

        $response->assertStatus(200);
        $response->assertSee($item->name);
        $response->assertSee($item->brand_name);
        $response->assertSee(number_format($item->price));
        $response->assertSee($item->description);
        $response->assertSee($item->condition);
        $response->assertSee($item->likes_count);
        $response->assertSee($item->comments_count);
        $response->assertSee($item->image);

        // カテゴリ情報
        foreach ($categories as $category) {
            $response->assertSee($category->name);
        }

        // コメント情報
        $response->assertSee($comment->comment);
        $response->assertSee($commentUser->name);
    }
}
