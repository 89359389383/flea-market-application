<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExhibitionTest extends TestCase
{
    use RefreshDatabase; // テストごとにデータベースをリセット

    /**
     * ✅ 1. 商品を正しく出品できるか確認するテスト
     */
    public function test_user_can_exhibit_item()
    {
        // 1. ストレージのモックを作成
        Storage::fake('public');

        // 2. ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // カテゴリを複数作成（商品に紐付けるため）
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // 4. 画像ファイルのモックを作成
        $image = UploadedFile::fake()->create('test_image.jpg', 100);

        // 5. 出品する商品のデータを準備（複数カテゴリを設定）
        $itemData = [
            'name' => 'テスト商品',
            'description' => 'これはテスト商品の説明です。',
            'brand_name' => 'テストブランド',
            'condition' => '良好',
            'price' => 5000,
            'image' => $image,
            'categories' => [$category1->id, $category2->id],
        ];

        // 6. 商品出品リクエストを送信（POST）
        $response = $this->post(route('items.store'), $itemData);

        // 7. データベースに商品が保存されたか確認
        $this->assertDatabaseHas('items', [
            'name' => $itemData['name'],
            'description' => $itemData['description'],
            'brand_name' => $itemData['brand_name'],
            'condition' => $itemData['condition'],
            'price' => $itemData['price'],
        ]);

        // 8. 画像が正しく保存されたか確認
        Storage::disk('public')->assertExists('items/' . $image->hashName());

        // 9. カテゴリの関連付けが行われたか確認（複数カテゴリ）
        $item = Item::where('name', 'テスト商品')->first();
        $this->assertNotNull($item, 'アイテムがDBに存在しません');
        $this->assertTrue($item->categories->contains($category1->id), 'カテゴリ1が正しく保存されていません');
        $this->assertTrue($item->categories->contains($category2->id), 'カテゴリ2が正しく保存されていません');

        // 10. 出品後のリダイレクト先を確認（トップページへ）
        $response->assertRedirect('/');
    }
}
