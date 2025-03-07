<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase; // データベースをリセットする機能を有効化

    /**
     * プロフィール編集画面の初期値が正しく表示されることを確認する
     */
    public function test_profile_page_displays_initial_values_correctly()
    {
        // 1. テスト用のユーザーを作成する
        //    -> factoryを使って仮のユーザーをデータベースに保存する
        $user = User::factory()->create()->first(); // first()メソッドを使用して単一のユーザーを取得

        // 2. ユーザーとしてログインする
        //    -> actingAs() を使って認証済みの状態を作成
        $this->actingAs($user);

        // 3. プロフィールページ（/mypage/profile）を開く
        //    -> get() を使ってプロフィールページにアクセス
        $response = $this->get('/mypage/profile');

        // 4. 各項目の初期値が正しく表示されていることを確認する
        //    -> assertSee() でページに表示されていることをチェック
        $response->assertStatus(200) // ステータスコードが200 (正常) であることを確認
            ->assertSee($user->profile_image) // プロフィール画像のパスが表示されていること
            ->assertSee($user->name) // ユーザー名が表示されていること
            ->assertSee($user->postal_code) // 郵便番号が表示されていること
            ->assertSee($user->address); // 住所が表示されていること
    }
}
