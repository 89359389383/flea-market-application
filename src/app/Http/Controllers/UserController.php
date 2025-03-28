<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Item;
use App\Http\Requests\ProfileRequest;

class UserController extends Controller
{
    /**
     * ユーザーのプロフィール情報を表示するメソッド
     * URL: /mypage
     * メソッド: GET (認証必須)
     */
    public function show(Request $request)
    {
        // 現在ログインしているユーザーの情報を取得します
        $user = auth()->user();
        // URLのクエリパラメータから表示するタブを取得します（デフォルトは'sell'）
        $tab = $request->query('page', 'sell'); 

        // タブの種類に応じて表示する商品を取得します
        if ($tab === 'buy') {
            // 購入した商品を取得します
            $items = $user->purchases()->with('item')->get()->map(fn($purchase) => $purchase->item);
        } else {
            // 出品した商品を取得します
            $items = Item::where('user_id', $user->id)->get();
        }

        // ビュー(user/show.blade.php)にデータを渡して表示します
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

        // ユーザーが購入した商品のみ取得（購入履歴の item データ）
        try {
            $purchasedItems = $user->purchases()->with('item')->get()->map(function ($purchase) {
                return $purchase->item;
            });
        } catch (\Exception $e) {
            return redirect()->route('user.show')->with('error', '購入履歴の取得中にエラーが発生しました。');
        }

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
