<?php

// 名前空間を定義します。これにより、このファイルがLaravelのコントローラーフォルダにあることを示します。
namespace App\Http\Controllers;

// 必要なクラスをインポートします。
// Requestクラスは、ユーザーのリクエストデータを取得するために使います。
// Addressモデルは、住所データを操作するために使用します。
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\User;

class AddressController extends Controller
{
    /**
     * 住所変更フォームを表示するメソッド
     * URL: /purchase/address/{item_id}
     * メソッド: GET
     */
    public function edit($item_id)
    {
        // 現在ログインしているユーザーの情報を取得します。
        $user = auth()->user();

        // ユーザーがすでに登録している住所を取得します。
        // このプロジェクトでは、ユーザーの住所は `users` テーブルに保存されているようです。
        // そのため、ユーザーのデータを直接使用します。
        $address = [
            'postal_code' => $user->postal_code, // 郵便番号
            'address' => $user->address, // 住所
            'building' => $user->building // 建物名
        ];

        // purchase/address_edit.blade.php ビューを表示し、住所データと商品IDを渡します。
        return view('purchase.address_edit', ['address' => $address, 'item_id' => $item_id]);
    }

    /**
     * ユーザーの住所を更新するメソッド
     * URL: /purchase/address/{item_id}
     * メソッド: POST
     */
    public function update(Request $request, $item_id)
    {
        // 現在ログインしているユーザーの情報を取得します。
        $user = User::find(auth()->id());

        // バリデーションはここでは記述しません。
        // （このプロジェクトではバリデーションは別ファイルで行うように指定されているためです。）

        // リクエストから新しい住所情報を取得して、ユーザー情報を更新します。
        $user->postal_code = $request->input('postal_code'); // フォームから送信された郵便番号を取得
        $user->address = $request->input('address'); // フォームから送信された住所を取得
        $user->building = $request->input('building'); // フォームから送信された建物名を取得

        // ユーザーの情報をデータベースに保存します。
        $user->save();

        // 商品購入画面にリダイレクトし、「住所を変更しました」というメッセージを表示します。
        return redirect("/purchase/{$item_id}")->with('success', '住所を変更しました。');
    }
}
