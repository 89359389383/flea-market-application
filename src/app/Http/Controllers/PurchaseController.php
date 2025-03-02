<?php

// 名前空間を指定します。これにより、コントローラーの場所が分かります。
namespace App\Http\Controllers;

// Requestクラスとモデルを使用するためにインポートします。
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Item;
use App\Http\Requests\PurchaseRequest;

class PurchaseController extends Controller
{
    /**
     * 商品購入ページを表示するメソッド
     * URL: /purchase/{item_id}
     * メソッド: GET (認証必須)
     */
    public function show($item_id)
    {
        // 商品のIDを使って、データベースから商品の情報を取得します。
        // 'findOrFail'は商品が見つからない場合、404エラーを自動的に表示します。
        $item = Item::findOrFail($item_id);

        // 現在ログインしているユーザーの情報を取得します。
        $user = auth()->user();

        // ビューに商品情報とユーザー情報を渡して表示します。
        return view('purchase.show', [
            'item' => $item,
            'user' => $user
        ]);
    }

    /**
     * 商品を購入するメソッド
     * URL: /purchase/{item_id}
     * メソッド: POST (認証必須)
     */
    public function store(PurchaseRequest $request, $item_id)
    {
        // データベースから購入する商品の情報を取得します（IDが一致しない場合はエラーを出す）
        $item = Item::findOrFail($item_id);

        // 新しい購入情報を一括で保存します (Purchase::create() を使用)
        $purchase = Purchase::create([
            'user_id' => auth()->id(),                  // 現在ログインしているユーザーのIDを保存します
            'item_id' => $item->id,                    // 購入した商品のIDを保存します
            'postal_code' => $request->input('postal_code'), // 郵便番号を取得して保存します
            'address' => $request->input('address'),         // 住所を取得して保存します
            'building' => $request->input('building'),       // 建物名を取得して保存します
            'payment_method' => $request->input('payment_method') // 支払い方法を取得して保存します
        ]);

        // 商品が購入されたことを示すために'sold'をtrueに設定し、データベースに保存します
        $item->update(['sold' => true]);

        // 購入が完了した後、ユーザーを商品一覧ページにリダイレクトし、成功メッセージを表示します。
        return redirect('/');
    }
}
