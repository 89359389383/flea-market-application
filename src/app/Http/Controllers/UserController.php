<?php

// 名前空間を定義します。これにより、Laravelはこのコントローラーを正しく認識できます。
namespace App\Http\Controllers;

use Illuminate\Http\Request; // HTTPリクエストを受け取るために必要です
use App\Models\User; // Userモデルを使用するためにインポートします
use App\Models\Item; // Itemモデルを使用してユーザーの商品を取得します
use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Log; // Logクラスをインポート

class UserController extends Controller
{
    /**
     * ユーザーのプロフィール情報を表示するメソッド
     * URL: /mypage
     * メソッド: GET (認証必須)
     */
    public function show(Request $request)
    {
        $user = auth()->user();
        $tab = $request->query('page', 'sell'); // デフォルトは'sell'

        if ($tab === 'buy') {
            $items = $user->purchases()->with('item')->get()->map(fn($purchase) => $purchase->item);
        } else {
            $items = Item::where('user_id', $user->id)->get();
        }

        return view('user.show', [
            'user' => $user,
            'items' => $items,
            'tab' => $tab
        ]);
    }

    /**
     * プロフィール編集ページを表示するメソッド
     * URL: /mypage/profile
     * メソッド: GET (認証必須)
     */
    public function edit()
    {
        // 現在ログインしているユーザーの情報を取得します
        $user = auth()->user();

        // ビュー(user/edit.blade.php)にユーザー情報を渡して表示します
        return view('user.edit', ['user' => $user]);
    }

    /**
     * プロフィール情報を更新するメソッド
     * URL: /mypage/profile
     * メソッド: POST (認証必須)
     */
    public function update(ProfileRequest $request)
    {
        // 現在ログインしているユーザーの情報を取得します
        $user = User::find(auth()->id());

        // ユーザー情報を一括更新 (updateメソッドを使用)
        $user->update([
            'name' => $request->input('name'),                // ユーザー名を更新
            'postal_code' => $request->input('postal_code'),  // 郵便番号を更新
            'address' => $request->input('address'),          // 住所を更新
            'building' => $request->input('building'),        // 建物名を更新
            'profile_image' => $request->hasFile('profile_image')  // プロフィール画像がアップロードされた場合
                ? $request->file('profile_image')->store('profiles', 'public') // 画像を保存し、パスを取得
                : $user->profile_image // 画像がない場合は元の画像パスを保持
        ]);

        // プロフィール更新後にプロフィールページにリダイレクトし、成功メッセージを表示します
        return redirect()->route('user.show')->with('success', 'プロフィールを更新しました。');
    }

    /**
     * ユーザーが購入した商品の一覧を表示するメソッド
     * URL: /mypage?tab=buy
     * メソッド: GET (認証必須)
     */
    public function buyList()
    {
        // 現在のユーザーを取得
        $user = auth()->user();
        Log::info('現在のユーザー情報:', ['user_id' => $user->id, 'user_name' => $user->name]);

        // ユーザーが購入した商品のみ取得（購入履歴の item データ）
        try {
            $purchasedItems = $user->purchases()->with('item')->get()->map(function ($purchase) {
                return $purchase->item;
            });
            Log::info('購入履歴の取得に成功:', ['items_count' => $purchasedItems->count()]);
        } catch (\Exception $e) {
            Log::error('購入履歴の取得中にエラーが発生:', ['error_message' => $e->getMessage()]);
            return redirect()->route('user.show')->with('error', '購入履歴の取得中にエラーが発生しました。');
        }

        // デバッグ用ログ（不要なら削除）
        Log::info('購入した商品データ:', ['items_count' => $purchasedItems->count(), 'items' => $purchasedItems]);

        // tab を 'buy' に設定してビューへ渡す
        return view('user.show', [
            'items' => $purchasedItems,
            'tab' => 'buy'
        ]);
    }

    /**
     * ユーザーが出品した商品の一覧を表示するメソッド
     * URL: /mypage?tab=sell
     * メソッド: GET (認証必須)
     */
    public function sellList()
    {
        // 現在ログインしているユーザーを取得します
        $user = auth()->user();

        // ユーザーが出品した商品をデータベースから取得します
        $items = Item::where('user_id', $user->id)->get();

        // ビュー(user/show.blade.php)に商品データを渡して表示します
        return view('user.show', ['items' => $items, 'tab' => 'sell']);
    }
}
