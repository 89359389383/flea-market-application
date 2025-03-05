<?php

use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase; // データベースをリセットする設定

    /** @test */
    public function 商品詳細ページに必要な情報が表示される()
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $user->id,
            'name' => 'テスト商品',
            'brand_name' => 'テストブランド',
            'price' => 5000,
            'description' => 'これはテスト用の商品です',
            'condition' => '良好', // 修正済み
            'likes_count' => 10,
            'comments_count' => 5
        ]);

        // **修正: 'item.show' → 'items.show'**
        $response = $this->get(route('items.show', ['item_id' => $item->id]));

        $response->assertStatus(200);
        $response->assertSee($item->name);
        $response->assertSee($item->brand_name);
        $response->assertSee(number_format($item->price));
        $response->assertSee($item->description);
        $response->assertSee($item->condition);
        $response->assertSee($item->likes_count);
        $response->assertSee($item->comments_count);
    }

    /** @test */
    public function 商品詳細ページに複数のカテゴリが表示される()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['user_id' => $user->id]);

        $categories = Category::factory()->count(3)->create();
        $item->categories()->attach($categories->pluck('id'));

        // **修正: 'item.show' → 'items.show'**
        $response = $this->get(route('items.show', ['item_id' => $item->id]));

        $response->assertStatus(200);
        foreach ($categories as $category) {
            $response->assertSee($category->name);
        }
    }
}
