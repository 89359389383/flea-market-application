<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    // テストごとにデータベースをリセット（初期化）する
    use RefreshDatabase, MakesHttpRequests, WithFaker;

    /**
     * 名前が未入力の場合、バリデーションエラーが発生するかテスト
     */
    public function test_name_is_required()
    {
        // LaravelのHTTPリクエストを使って、会員登録APIをPOSTで送信
        $response = $this->post('/register', [
            'name' => '', // 名前を空にする
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // レスポンスに「お名前を入力してください」というバリデーションエラーが含まれることを確認
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションエラーが発生するかテスト
     */
    public function test_email_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // エラーメッセージ「メールアドレスを入力してください」が返ることを確認
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /**
     * パスワードが未入力の場合、バリデーションエラーが発生するかテスト
     */
    public function test_password_is_required()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '', // パスワードを空にする
            'password_confirmation' => '',
        ]);

        // エラーメッセージ「パスワードを入力してください」が返ることを確認
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /**
     * パスワードが7文字以下の場合、バリデーションエラーが発生するかテスト
     */
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123', // 7文字のパスワード
            'password_confirmation' => 'pass123',
        ]);

        // エラーメッセージ「パスワードは8文字以上で入力してください」が返ることを確認
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * 確認用パスワードと一致しない場合、バリデーションエラーが発生するかテスト
     */
    public function test_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrongpassword', // 確認用パスワードが異なる
        ]);

        // エラーメッセージ「パスワードと一致しません」が返ることを確認
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    /**
     * 正しい情報が入力された場合、会員登録が成功しログイン画面にリダイレクトされるかテスト
     */
    public function test_successful_registration_redirects_to_login()
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 会員登録が成功すると、プロフィール画面へリダイレクトすることを確認
        $response->assertRedirect('/mypage/profile');

        // データベースにユーザーが登録されているか確認
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
}
