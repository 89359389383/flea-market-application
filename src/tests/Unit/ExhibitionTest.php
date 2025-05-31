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
    use RefreshDatabase; // この命令によって、毎回テストの前にデータベースの状態をリセット（きれいに）する

    /**
     * ✅ 1. 商品を正しく出品できるか確認するテスト
     */
    public function test_user_can_exhibit_item()
    {
        // 1. ストレージのモック（にせものの保存場所）を作成
        // 本物の画像保存先（storage/app/public）を使わず、テスト用の空の保存場所を使う
        // テスト後に自動で削除されるので、本番データに影響がない
        Storage::fake('public');

        // 2. ユーザーを作成し、ログイン状態にする
        // factoryを使ってユーザーを1人作成し、ログイン状態にしてテストを進める
        $user = User::factory()->create()->first();
        $this->actingAs($user);

        // 3. カテゴリを2つ作成（商品にひも付けるため）
        // 複数カテゴリを持つ商品の出品テストなので、テスト用にカテゴリも作る
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        // 4. アップロードされた画像のモック（にせもの）を作成
        // 実際の画像ファイルを使わず、テスト用に仮の画像ファイル（test_image.jpg・100KB）を作る
        $image = UploadedFile::fake()->create('test_image.jpg', 100);

        // 5. 出品する商品のデータを用意
        // 入力フォームから送られてくる内容を配列で用意し、カテゴリも2つ指定
        $itemData = [
            'name' => 'テスト商品',
            'description' => 'これはテスト商品の説明です。',
            'brand_name' => 'テストブランド',
            'condition' => '良好',
            'price' => 5000,
            'image' => $image,
            'categories' => [$category1->id, $category2->id],
        ];

        // 6. 出品処理を実行（ルート items.store にPOSTで送信）
        // フォームから送られたデータと同じ形式で送って、出品機能が正しく動作するかテストする
        $response = $this->post(route('items.store'), $itemData);

        // 7. データベースに商品情報が正しく保存されたか確認
        // items テーブルに、入力した情報と一致する商品が存在しているかチェック
        $this->assertDatabaseHas('items', [
            'name' => $itemData['name'],
            'description' => $itemData['description'],
            'brand_name' => $itemData['brand_name'],
            'condition' => $itemData['condition'],
            'price' => $itemData['price'],
        ]);

        // 8. アップロードした画像が、にせものの保存先に正しく保存されたか確認
        // Storage::disk('public') で public フォルダの中を操作する準備をし、
        // $image->hashName() で自動生成されたファイル名をもとに保存されているかチェックする
        $disk = Storage::disk('public');
        $disk->assertExists('items/' . $image->hashName());

        // 9. 商品がカテゴリと正しくひも付いて保存されているか確認（多対多の関係）
        // 商品名からデータベース上の商品を取り出して、それが null（見つからない）でないか確認
        // さらに、その商品にカテゴリ1とカテゴリ2がひも付いているかどうかを確認する
        $item = Item::where('name', 'テスト商品')->first();
        $this->assertNotNull($item, 'アイテムがDBに存在しません'); // 商品が保存されているか
        $this->assertTrue($item->categories->contains($category1->id), 'カテゴリ1が正しく保存されていません');
        $this->assertTrue($item->categories->contains($category2->id), 'カテゴリ2が正しく保存されていません');

        // 10. 出品完了後、トップページ（/）へリダイレクトされるか確認
        $response->assertRedirect('/');
    }
}
