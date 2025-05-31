<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase; // テストが終わるたびにデータベースをリセットする

    /**
     * ✅ 1. ログアウト後にトップページへリダイレクトされることを確認するテスト
     */
    public function test_user_can_logout_successfully()
    {
        // 1. テスト用のユーザーをデータベースに作成
        $user = User::factory()->create()->first();

        // 2. 作成したユーザーでログインする
        $this->actingAs($user);

        // 3. ログアウトAPIを呼び出す
        $response = $this->post('/logout');

        // 4. ステータスコード 302（リダイレクト）が返ることを確認
        $response->assertStatus(302);

        // 5. ログアウト後のリダイレクト先が `/` であることを確認
        $response->assertRedirect('/');

        // 6. ユーザーが未認証状態になったことを確認
        $this->assertGuest();
    }

    /**
     * ✅ 2. 未ログイン状態でログアウトを試みた場合のテスト
     */
    public function test_guest_cannot_logout()
    {
        // 1. 未ログイン状態でログアウトAPIを呼び出す
        $response = $this->post('/logout');

        // 2. ステータスコード 302（リダイレクト）が返ることを確認
        $response->assertStatus(302);

        // 3. ログアウト後のリダイレクト先が `/` であることを確認
        $response->assertRedirect('/');

        // 4. ユーザーは依然として未認証の状態であることを確認
        $this->assertGuest();
    }
}
