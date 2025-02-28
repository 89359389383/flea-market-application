<?php

// 名前空間を定義します。これにより、Laravelはこのコントローラーを正しく認識できます。
namespace App\Http\Controllers;

use Illuminate\Http\Request; // HTTPリクエストを受け取るために必要です
use App\Models\User; // Userモデルを使用するためにインポートします
use App\Models\Item; // Itemモデルを使用してユーザーの商品を取得します
use App\Http\Requests\ProfileRequest;

class UserController extends Controller
{
    /**
     * ユーザーのプロフィール情報を表示するメソッド
     * URL: /mypage
     * メソッド: GET (認証必須)
     */
    public function show()
    {
        // 現在ログインしているユーザーの情報を取得します
        $user = auth()->user();

        // 出品した商品を取得
        $products = $user->items;

        // ビュー(user/show.blade.php)にユーザー情報を渡して表示します
        return view('user.show', ['user' => $user, 'products' => $products]);
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
        // 現在ログインしているユーザーを取得します
        $user = User::find(auth()->id()); // 現在のユーザーをUserモデルから取得

        // ユーザーが購入した商品をデータベースから取得します
        $items = $user->purchases()->with('item')->get();

        // ビュー(user/show.blade.php)に商品データを渡して表示します
        return view('user.show', ['items' => $items, 'tab' => 'buy']);
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
