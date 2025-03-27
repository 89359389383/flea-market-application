<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;

class SearchTest extends TestCase
{
    use RefreshDatabase; // データベースをリセットしてテストを独立させる

    /**
     * 商品名で部分一致検索ができることを確認するテスト
     */
    public function test_user_can_search_items_by_partial_match()
    {
        // テスト用の商品データを作成
        Item::factory()->create(['name' => '腕時計']);
        Item::factory()->create(['name' => 'HDD']);
        Item::factory()->create(['name' => '時計スタンド']);

        // 検索キーワードを設定
        $searchKeyword = '時計';

        // 検索APIを叩く
        $response = $this->get('/search?name=' . $searchKeyword);

        // レスポンスの内容を検証
        $response->assertStatus(200);
        $response->assertSee('腕時計'); // 「時計」を含む商品が表示されているか確認
        $response->assertSee('時計スタンド');
        $response->assertDontSee('HDD'); // 関係ない商品は表示されないことを確認
    }

    /**
     * 検索状態がマイリストでも保持されていることを確認するテスト
     */
    public function test_search_state_is_retained_in_mylist()
    {
        // テストユーザーを作成
        $user = User::factory()->create(); // ユーザーを作成
        $user = User::first(); // 単一のユーザーを取得
        $this->actingAs($user); // 作成したユーザーでログイン

        // 検索対象の商品を作成
        $notebook = Item::factory()->create(['name' => 'ノートPC']);
        Item::factory()->create(['name' => 'スマートフォン']);

        // ノートPCをマイリスト（お気に入り）に登録する
        $this->post("/items/{$notebook->id}/toggle-like");

        // 検索キーワードを設定
        $searchKeyword = 'ノート';

        // ホームページで検索を実行
        $response = $this->get('/search?name=' . $searchKeyword);

        // 検索結果を確認
        $response->assertStatus(200);
        $response->assertSee('ノートPC');
        $response->assertDontSee('スマートフォン'); // 検索キーワードに一致しない商品は表示されない

        // マイリストページへ遷移
        $response = $this->get('/?tab=mylist&name=' . $searchKeyword);

        // 検索状態が保持されていることを確認
        $response->assertStatus(200);
        $response->assertSee('ノートPC'); // マイリストでも検索結果が反映されている
    }
}
