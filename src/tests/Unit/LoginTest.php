<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase; // テスト実行ごとにデータベースをリセット

    /**
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_if_email_is_missing()
    {
        // 1. パスワードのみ入力し、メールアドレスは入力しない
        $response = $this->post('/login', [
            'password' => 'password123'
        ]);

        // 2. バリデーションエラーメッセージが返ってくることを確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_if_password_is_missing()
    {
        // 1. メールアドレスのみ入力し、パスワードは入力しない
        $response = $this->post('/login', [
            'email' => 'test@example.com'
        ]);

        // 2. バリデーションエラーメッセージが返ってくることを確認
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_if_credentials_are_wrong()
    {
        // 1. 存在しないメールアドレスとパスワードを入力
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword'
        ]);

        // 2. 「ログイン情報が登録されていません」のメッセージが表示されることを確認
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
    }

    /**
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_user_can_login_with_correct_credentials()
    {
        // 1. 事前にデータベースにユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        // 2. 正しい情報でログインを試みる
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // 3. ログイン成功後にトップページにリダイレクトすることを確認
        $response->assertRedirect('/');

        // 4. ユーザーがログイン状態であることを確認
        $this->assertAuthenticatedAs($user);
    }
}
