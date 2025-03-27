<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィール編集画面の初期値が正しく表示されることを確認する
     */
    public function test_profile_page_displays_initial_values_correctly()
    {
        // ストレージのモック
        Storage::fake('public');

        // プロフィール画像のパスを仮定（storage/app/public/profile_images に保存される想定）
        $profileImagePath = 'profile_images/test_profile.jpg';

        // ユーザー作成（profile_image パスを保存）
        $user = User::factory()->create([
            'profile_image' => $profileImagePath,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
        ]);

        // 認証状態でアクセス
        $this->actingAs($user);

        // プロフィール編集ページにアクセス
        $response = $this->get('/mypage/profile');

        // HTML内に <img src="storage/..."> があるかを確認
        $expectedImgTag = '<img id="image-preview" src="' . asset('storage/' . $profileImagePath) . '" alt="プロフィール画像" class="profile-preview">';

        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->postal_code)
            ->assertSee($user->address)
            ->assertSee($profileImagePath) // パスとして含まれていること
            ->assertSee($expectedImgTag, false); // タグとして正しく表示されていること
    }
}
