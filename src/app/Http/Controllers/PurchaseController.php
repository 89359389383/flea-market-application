<?php

// 名前空間を指定します。これにより、コントローラーの場所が分かります。
namespace App\Http\Controllers;

// Requestクラスとモデルを使用するためにインポートします。
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Item;
use App\Http\Requests\PurchaseRequest;
use Illuminate\Support\Facades\Log; // Logクラスをインポート
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB; // DBクラスをインポート

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
            Log::info('【購入処理開始】', ['user_id' => auth()->id(), 'item_id' => $item_id]);

            $item = Item::findOrFail($item_id);
            Log::info('【商品取得成功】', ['item_id' => $item->id, 'sold_status' => $item->sold]);

            if ($item->sold) {
                Log::warning('【エラー】すでに売り切れの商品が購入されようとしました', ['item_id' => $item_id]);
                return redirect()->route('items.show', $item_id)->with('error', 'この商品はすでに売り切れです。');
            }

            // デバッグ用ログ（リクエストデータの詳細を記録）
            $requestData = [
                'user_id' => auth()->id(),
                'item_id' => $item->id,
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
                'payment_method' => $request->input('payment_method'),
            ];
            Log::info('【受信したリクエストデータ】', $requestData);

            // payment_method のトリミング後のデータを確認
            $trimmedPaymentMethod = trim($request->input('payment_method'));
            Log::info('【トリミング後の支払い方法】', ['payment_method' => $trimmedPaymentMethod]);

            // データベースの `enum` の値を取得して比較
            $validPaymentMethods = DB::select("SHOW COLUMNS FROM purchases WHERE Field = 'payment_method'");
            Log::info('【データベースの payment_method カラム情報】', ['enum_values' => $validPaymentMethods]);

            // データ挿入前の最終確認
            Log::info('【購入データを挿入】', [
                'user_id' => auth()->id(),
                'item_id' => $item->id,
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
                'payment_method' => $trimmedPaymentMethod,
            ]);

            // データを挿入
            Purchase::create([
                'user_id' => auth()->id(),
                'item_id' => $item->id,
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
                'payment_method' => $trimmedPaymentMethod,
            ]);

            Log::info('【購入データ挿入成功】');

            // 商品の状態を「sold」に更新
            $item->update(['sold' => true]);
            Log::info('【商品ステータス更新】', ['item_id' => $item->id, 'sold_status' => $item->sold]);

            return redirect()->route('items.index');
        } catch (\Exception $e) {
            Log::error('【購入処理中にエラー発生】', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            // 追加情報（データベースの payment_method の値を取得）
            $existingPaymentMethods = DB::table('purchases')->select('payment_method')->distinct()->get();
            Log::error('【既存の payment_method の値】', ['values' => $existingPaymentMethods]);

            return redirect()->back()->with('error', '購入処理に失敗しました。');
        }
    }

    public function checkout($item_id)
    {
        try {
            $item = Item::findOrFail($item_id);
            $stripePublicKey = env('STRIPE_PUBLIC'); // .env から公開キーを取得

            // Stripe の決済ページ URL（仮）
            $stripeCheckoutUrl = "https://checkout.stripe.com/pay/test_checkout_session";

            Log::info("Stripe決済ページに遷移", [
                'user_id' => auth()->id(),
                'item_id' => $item_id,
                'url' => $stripeCheckoutUrl
            ]);

            return redirect()->away($stripeCheckoutUrl);
        } catch (\Exception $e) {
            Log::error("Stripe決済ページのリダイレクトに失敗", ['error' => $e->getMessage()]);
            return redirect()->route('purchase.show', ['item_id' => $item_id])
                ->with('error', '決済画面への遷移に失敗しました。');
        }
    }
}
