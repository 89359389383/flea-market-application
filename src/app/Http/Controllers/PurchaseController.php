<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Item;
use App\Http\Requests\PurchaseRequest;
use Stripe\Stripe;
use Stripe\Checkout\Session;

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
        try {
            $item = Item::findOrFail($item_id);

            if ($item->sold) {
                return redirect()->route('items.show', $item_id)->with('error', 'この商品はすでに売り切れです。');
            }

            // payment_method のトリミング
            $trimmedPaymentMethod = trim($request->input('payment_method'));

            // データを挿入
            Purchase::create([
                'user_id' => auth()->id(),
                'item_id' => $item->id,
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
                'payment_method' => $trimmedPaymentMethod,
            ]);

            // 商品の状態を「sold」に更新
            $item->update(['sold' => true]);

            return redirect()->route('items.index');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '購入処理に失敗しました。');
        }
    }

    /**
     * Stripe 決済ページへリダイレクト
     */
    public function checkout($item_id)
    {
        try {
            // StripeのAPIキー設定
            $stripeKey = env('STRIPE_SECRET_KEY');
            Stripe::setApiKey($stripeKey);

            // 商品情報取得
            $item = Item::findOrFail($item_id);

            // ドメイン設定
            $YOUR_DOMAIN = env('APP_URL', 'http://localhost');

            // 画像URLの処理
            if (filter_var($item->image, FILTER_VALIDATE_URL)) {
                $image_url = $item->image;
            } else {
                $image_url = asset('storage/' . $item->image);
            }
            $image_url = str_replace('+', '%20', $image_url);

            // 日本円の価格調整（JPYの場合は100倍しない）
            $currency = 'jpy';
            $unit_amount = ($currency === 'jpy') ? $item->price : $item->price * 100;

            // Stripe Checkout セッションの作成
            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $item->name,
                            'images' => [$image_url],
                        ],
                        'unit_amount' => $unit_amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $YOUR_DOMAIN . '/purchase/complete/' . $item_id, // ← ここを変更
                'cancel_url' => $YOUR_DOMAIN . '/cancel',
            ]);

            return redirect($checkout_session->url);
        } catch (\Exception $e) {
            return redirect()->route('purchase.show', ['item_id' => $item_id])
                ->with('error', '決済画面への遷移に失敗しました。');
        }
    }

    // Stripe支払い後に呼び出される処理（storeのロジック再利用）
    public function complete($item_id)
    {
        try {
            $item = Item::findOrFail($item_id);

            if ($item->sold) {
                return redirect()->route('items.show', $item_id)->with('error', 'この商品はすでに売り切れです。');
            }

            $user = auth()->user();

            Purchase::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'postal_code' => $user->postal_code,
                'address' => $user->address,
                'building' => $user->building,
                'payment_method' => 'カード払い', // ★固定
            ]);

            $item->update(['sold' => true]);

            return redirect()->route('items.index')->with('success', '購入が完了しました');
        } catch (\Exception $e) {
            return redirect()->route('items.show', $item_id)->with('error', '購入処理に失敗しました');
        }
    }
}
