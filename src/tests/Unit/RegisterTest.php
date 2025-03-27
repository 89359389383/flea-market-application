<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase, MakesHttpRequests, WithFaker;

    public function test_name_is_required()
    {
        // 1. 会員登録ページを開く
        $this->get(route('register.show'));

        // 2. 名前を入力せずに、他の必要項目を入力
        $formData = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 3. 登録ボタンを押す
        $response = $this->post(route('register.store'), $formData);

        // 期待されるバリデーションメッセージを確認
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    public function test_email_is_required()
    {
        $this->get(route('register.show'));

        $formData = [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $formData);

        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    public function test_password_is_required()
    {
        $this->get(route('register.show'));

        $formData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];

        $response = $this->post(route('register.store'), $formData);

        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    public function test_password_must_be_at_least_8_characters()
    {
        $this->get(route('register.show'));

        $formData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123', // 7文字
            'password_confirmation' => 'pass123',
        ];

        $response = $this->post(route('register.store'), $formData);

        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    public function test_password_confirmation_must_match()
    {
        $this->get(route('register.show'));

        $formData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrongpassword',
        ];

        $response = $this->post(route('register.store'), $formData);

        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    public function test_successful_registration_redirects_to_login()
    {
        $this->get(route('register.show'));

        $formData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $formData);

        // メール認証画面へリダイレクトされることを確認
        $response->assertRedirect('/email/verify');

        // ユーザーがDBに登録されていることを確認
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
}
