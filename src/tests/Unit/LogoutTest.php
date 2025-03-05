<?php

namespace Tests\Feature;

use App\Models\User; // ユーザーモデルを使用するためにインポート
use Illuminate\Foundation\Testing\RefreshDatabase; // テスト後にデータベースをリセットするためのトレイト
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase; // テストが終わるたびにデータベースをリセットする

    /**
     * ログアウト後にログインページへリダイレクトされることを確認するテスト
     */
    public function test_user_can_logout_successfully()
    {
        // 1. テスト用のユーザーをデータベースに作成
        $user = User::factory()->create()->first(); // 単一のユーザーインスタンスを取得

        // 2. テスト用のユーザーとしてログイン
        $this->actingAs($user); // ここで $user は User モデルのインスタンス

        // 3. ログアウトAPIを呼び出す
        $response = $this->post('/logout'); // `Fortify`のデフォルトのログアウトエンドポイント

        // 4. ログアウト後のステータスコードが `302`（リダイレクト）であることを確認
        $response->assertStatus(302);

        // 5. ログアウト後のリダイレクト先が `/` であることを確認
        $response->assertRedirect('/');

        // 6. ユーザーが未認証状態になったことを確認
        $this->assertGuest();
    }

    /**
     * 未ログイン状態でログアウトを試みた場合のテスト
     */
    public function test_guest_cannot_logout()
    {
        // 1. 未ログイン状態でログアウトAPIを呼び出す
        $response = $this->post('/logout');

        // 2. 期待されるHTTPステータスコードが `302` （リダイレクト）であることを確認
        $response->assertStatus(302);

        // 3. ログアウト後のリダイレクト先が `/` であることを確認
        $response->assertRedirect('/');

        // 4. ユーザーは依然として未認証の状態であることを確認
        $this->assertGuest();
    }
}
