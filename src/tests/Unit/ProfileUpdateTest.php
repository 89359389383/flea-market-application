<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ✅ 1. プロフィール編集画面の初期値が正しく表示されることを確認するテスト
     */
    public function test_profile_page_displays_initial_values_correctly()
    {
        // 1. ストレージのモックを設定
        Storage::fake('public');

        // 2. プロフィール画像のパスを設定
        $profileImagePath = 'profile_images/test_profile.jpg';

        // 3. ユーザーを作成（プロフィール画像、郵便番号、住所を設定）
        $user = User::factory()->create([
            'profile_image' => $profileImagePath,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
        ]);

        // 4. 作成したユーザーでログインする
        $this->actingAs($user);

        // 5. プロフィール編集ページにアクセス
        $response = $this->get('/mypage/profile');

        // 6. 期待される画像タグを設定
        $expectedImgTag = '<img id="image-preview" src="' . asset('storage/' . $profileImagePath) . '" alt="プロフィール画像" class="profile-preview">';

        // 7. レスポンスの検証
        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->postal_code)
            ->assertSee($user->address)
            ->assertSee($profileImagePath) // パスとして含まれていること
            ->assertSee($expectedImgTag, false); // タグとして正しく表示されていること
    }
}
