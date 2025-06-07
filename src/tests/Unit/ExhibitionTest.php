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
    use RefreshDatabase; // テストごとにDBを初期化

    /**
     * ✅ 1. 商品を正しく出品できるか確認するテスト
     */
    public function test_user_can_exhibit_item()
    {
        // 1. ストレージのモック作成（本物の画像保存場所を使わない）
        Storage::fake('public');

        // 2. ユーザー作成＆ログイン
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 3. カテゴリ2つ作成
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // 4. アップロード画像のモックを作成
        $image = UploadedFile::fake()->create('test_image.jpg', 100);

        // 5. 出品データ準備（カテゴリも複数指定）
        $itemData = [
            'name' => 'テスト商品',
            'description' => 'これはテスト商品の説明です。',
            'brand_name' => 'テストブランド',
            'condition' => '良好',
            'price' => 5000,
            'image' => $image,
            'categories' => [$category1->id, $category2->id],
        ];

        // 6. 出品処理POST送信
        $response = $this->post(route('items.store'), $itemData);

        // 7. DBに商品が正しく保存されているか確認
        $this->assertDatabaseHas('items', [
            'name' => $itemData['name'],
            'description' => $itemData['description'],
            'brand_name' => $itemData['brand_name'],
            'condition' => $itemData['condition'],
            'price' => $itemData['price'],
        ]);

        // 8. 画像がfakeストレージ内に保存されているか確認
        $disk = Storage::disk('public');
        $this->assertTrue($disk->exists('items/' . $image->hashName()));

        // 9. 商品がカテゴリと正しく紐付いているか確認
        $item = Item::where('name', 'テスト商品')->first();
        $this->assertNotNull($item, 'アイテムがDBに存在しません');
        $this->assertTrue($item->categories->contains($category1->id), 'カテゴリ1が正しく保存されていません');
        $this->assertTrue($item->categories->contains($category2->id), 'カテゴリ2が正しく保存されていません');

        // 10. 出品後、トップページへリダイレクトされるか確認
        $response->assertRedirect('/');
    }
}
