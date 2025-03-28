<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_if_email_is_missing()
    {
        // 1. ログインページを開く
        $this->get(route('login'));

        // 2. パスワードのみ入力し、メールアドレスは空にする
        $formData = [
            'email' => '',
            'password' => 'password123',
        ];

        // 3. ログインボタンを押す（POST送信）
        $response = $this->post(route('login.store'), $formData);

        // 4. エラーメッセージを確認
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * ✅ 2. パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_if_password_is_missing()
    {
        // 1. ログインページを開く
        $this->get(route('login'));

        // 2. メールアドレスのみ入力し、パスワードは空にする
        $formData = [
            'email' => 'test@example.com',
            'password' => '',
        ];

        // 3. ログインボタンを押す（POST送信）
        $response = $this->post(route('login.store'), $formData);

        // 4. エラーメッセージを確認
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * ✅ 3. 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function test_login_fails_if_credentials_are_wrong()
    {
        // 1. ログインページを開く
        $this->get(route('login'));

        // 2. 存在しないメールアドレスとパスワードを入力
        $formData = [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ];

        // 3. ログインボタンを押す（POST送信）
        $response = $this->post(route('login.store'), $formData);

        // 4. エラーメッセージを確認
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * ✅ 4. 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_user_can_login_with_correct_credentials()
    {
        // 1. ユーザーを事前に作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. ログインページを開く
        $this->get(route('login'));

        // 3. 正しい情報を入力
        $formData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // 4. ログインボタンを押す（POST送信）
        $response = $this->post(route('login.store'), $formData);

        // 5. トップページにリダイレクトされることを確認
        $response->assertRedirect('/');

        // 6. ユーザーが認証されていることを確認
        $this->assertAuthenticatedAs($user);
    }
}
