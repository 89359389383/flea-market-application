<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;

class SearchTest extends TestCase
{
    use RefreshDatabase; // データベースをリセットしてテストを独立させる

    /**
     * ✅ 1. 商品名で部分一致検索ができることを確認するテスト
     */
    public function test_user_can_search_items_by_partial_match()
    {
        // 1. テスト用の商品データを作成
        Item::factory()->create(['name' => '腕時計']);
        Item::factory()->create(['name' => 'HDD']);
        Item::factory()->create(['name' => '時計スタンド']);

        // 2. 検索キーワードを設定
        $searchKeyword = '時計';

        // 3. 検索APIを叩く
        $response = $this->get('/search?name=' . $searchKeyword);

        // 4. レスポンスの内容を検証
        $response->assertStatus(200);
        $response->assertSee('腕時計'); // 「時計」を含む商品が表示されているか確認
        $response->assertSee('時計スタンド');
        $response->assertDontSee('HDD'); // 関係ない商品は表示されないことを確認
    }

    /**
     * ✅ 2. 検索状態がマイリストでも保持されていることを確認するテスト
     */
    public function test_search_state_is_retained_in_mylist()
    {
        // 1. テストユーザーを作成
        $user = User::factory()->create();
        $user = User::first();
        $this->actingAs($user);

        // 2. 検索対象の商品を作成
        $notebook = Item::factory()->create(['name' => 'ノートPC']);
        Item::factory()->create(['name' => 'スマートフォン']);

        // 3. ノートPCをマイリストに登録
        $this->post("/items/{$notebook->id}/toggle-like");

        // 4. 検索キーワードを設定
        $searchKeyword = 'ノート';

        // 5. ホームページで検索を実行
        $response = $this->get('/search?name=' . $searchKeyword);

        // 6. 検索結果を確認
        $response->assertStatus(200);
        $response->assertSee('ノートPC');
        $response->assertDontSee('スマートフォン');

        // 7. マイリストページへ遷移
        $response = $this->get('/?tab=mylist&name=' . $searchKeyword);

        // 8. 検索状態が保持されていることを確認
        $response->assertStatus(200);
        $response->assertSee('ノートPC');
    }
}
