<?php

namespace Tests\Feature;

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
     * 商品を正しく出品できるかのテスト
     */
    public function test_user_can_exhibit_item()
    {
        // ストレージのモックを作成
        Storage::fake('public');

        // ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        $itemData = [
            'image' => UploadedFile::fake()->create('test_image.jpg', 100), // 100KB のダミーファイル
        ];
        $this->actingAs($user); // ユーザーをログイン状態にする

        // カテゴリを作成（商品に紐付けるため）
        $category = Category::factory()->create();

        // 画像ファイルのモックを作成
        $image = UploadedFile::fake()->create('test_image.jpg', 100); // 100KB のダミーファイル

        // 出品する商品のデータを準備
        $itemData = [
            'name' => 'テスト商品', // 商品名
            'description' => 'これはテスト商品の説明です。', // 商品説明
            'brand_name' => 'テストブランド',
            'condition' => '良好', // 商品の状態
            'price' => 5000, // 販売価格
            'image' => $image, // モックの画像をセット
            'categories' => [$category->id], // カテゴリIDを配列で指定
        ];

        // 商品出品リクエストを送信（POST）
        $response = $this->post(route('items.store'), $itemData);

        // データベースに商品が保存されたか確認
        $this->assertDatabaseHas('items', [
            'name' => $itemData['name'],
            'description' => $itemData['description'],
            'brand_name' => $itemData['brand_name'],
            'condition' => $itemData['condition'],
            'price' => $itemData['price'],
        ]);

        // 画像が正しく保存されたか確認
        Storage::disk('public')->assertExists('items/' . $image->hashName());

        // カテゴリの関連付けが行われたか確認
        $item = Item::where('name', 'テスト商品')->first();
        $this->assertNotNull($item, 'アイテムがDBに存在しません');
        $this->assertTrue($item->categories->contains($category->id), 'カテゴリが正しく保存されていません');

        // 出品後のリダイレクト先を確認（トップページへ）
        $response->assertRedirect('/');
    }

    /**
     * 必須項目が不足している場合のバリデーションテスト
     */
    public function test_item_exhibition_fails_due_to_validation()
    {
        // ユーザーを作成し、ログインする
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 不完全なデータ（画像なし）を送信
        $invalidData = [
            'name' => '',
            'description' => '',
            'condition' => '',
            'price' => '',
            'categories' => [],
        ];

        // リクエストを送信
        $response = $this->post(route('items.store'), $invalidData);

        // バリデーションエラーが発生したことを確認
        $response->assertSessionHasErrors([
            'name',
            'description',
            'condition',
            'price',
            'image', // 画像が必須のためエラーになるはず
            'categories'
        ]);
    }
}
