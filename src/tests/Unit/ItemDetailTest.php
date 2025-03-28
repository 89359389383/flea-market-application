<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. 商品詳細ページに必要な情報が表示されるか確認するテスト
     */
    public function test_item_detail_page_displays_required_information()
    {
        // 1. テストユーザーを作成
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        // 2. テスト商品を作成
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

        // 3. カテゴリを作成し、商品に紐づける
        $categories = Category::factory()->count(2)->create();
        $item->categories()->attach($categories->pluck('id'));

        // 4. コメントとコメントしたユーザー情報を追加
        $commentUser = User::factory()->create(['name' => 'コメントユーザー']);
        $comment = Comment::factory()->create([
            'item_id' => $item->id,
            'user_id' => $commentUser->id,
            'comment' => 'これはテストコメントです。',
        ]);

        // 5. 商品詳細ページを開く
        $response = $this->get(route('items.show', ['item_id' => $item->id]));

        // 6. ステータスコード 200（成功）が返ることを確認
        $response->assertStatus(200);

        // 7. 商品の基本情報が表示されているか確認
        $response->assertSee($item->name);
        $response->assertSee($item->brand_name);
        $response->assertSee(number_format($item->price));
        $response->assertSee($item->description);
        $response->assertSee($item->condition);
        $response->assertSee($item->likes_count);
        $response->assertSee($item->comments_count);
        $response->assertSee($item->image);

        // 8. カテゴリ情報が表示されているか確認
        foreach ($categories as $category) {
            $response->assertSee($category->name);
        }

        // 9. コメント情報が表示されているか確認
        $response->assertSee($comment->comment);
        $response->assertSee($commentUser->name);
    }
}
