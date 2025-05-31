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
        // 1. ストレージのモックを設定（テスト用の「にせものの保存場所」を用意する）
        // 実際の画像フォルダを使わず、テスト中はこの空の「にせものフォルダ」を使うようLaravelに指示する。
        // これにより本物のデータが汚れず、安心してテストできる。
        Storage::fake('public');

        // 2. プロフィール画像のパスを設定（使う画像の「場所」をメモする）
        // 本当の画像はまだ保存されてないけど、「このパスの画像が表示されてるはずだよね？」という前提でテストを進める。
        // 例: 'storage/profile_images/test_profile.jpg'
        $profileImagePath = 'profile_images/test_profile.jpg';

        // 3. ユーザーを作成（プロフィール画像、郵便番号、住所を設定）
        User::factory()->create([
            'profile_image' => $profileImagePath,
            'postal_code' => '123-4567',
            'address' => '東京都新宿区',
        ]);

        $user = User::first();

        // 4. ユーザーとしてログイン
        $this->actingAs($user);

        // 5. プロフィール編集ページにアクセス
        $response = $this->get('/mypage/profile');

        // 6. 期待される画像タグを設定（ページの中に表示されるはずの画像HTMLタグを作る）
        // 画像の表示タグ <img> を文字列で作って、あとで画面にこのタグが出ているかチェックするための見本として保存する。
        // asset() 関数で実際にアクセスできるURL（http://〜）を作って、それを src に入れている。
        // このタグが画面に出ていれば、「画像が正しく表示されている」と言える。
        $expectedImgTag = '<img id="image-preview" src="' . asset('storage/' . $profileImagePath) . '" alt="プロフィール画像" class="profile-preview">';

        // ↓以下のように使われている↓

        // ->assertSee($expectedImgTag, false); // タグとして正しく表示されていること
        // 上で作った画像タグが、画面のHTMLにそのまま入っているか？をチェックする。
        // false を指定することで、HTMLタグを「ただの文字列」としてではなく「HTMLそのもの」として探す。
        // 見本と完全に一致していることが求められるため、表示ミスがあればここで見つかる。

        // 7. レスポンスの検証
        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($user->postal_code)
            ->assertSee($user->address)
            ->assertSee($profileImagePath) // パスとして含まれていること
            ->assertSee($expectedImgTag, false); // タグとして正しく表示されていること
    }
}
