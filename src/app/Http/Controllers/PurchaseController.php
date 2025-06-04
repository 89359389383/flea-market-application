<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Item;
use App\Http\Requests\PurchaseRequest;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log; // Logファサードをインポート

class PurchaseController extends Controller
{
    /**
     * 商品購入ページを表示するメソッド
     * URL: /purchase/{item_id}
     * メソッド: GET (認証必須)
     */
    public function show($item_id)
    {
        Log::debug("showメソッドが呼び出されました。item_id: {$item_id}");

        // 商品のIDを使って、データベースから商品の情報を取得します。
        // 'findOrFail'は商品が見つからない場合、404エラーを自動的に表示します。
        $item = Item::findOrFail($item_id);
        Log::debug("商品情報を取得しました。", ['item' => $item]);

        // 現在ログインしているユーザーの情報を取得します。
        $user = auth()->user();
        Log::debug("ログインユーザー情報を取得しました。", ['user' => $user]);

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
        Log::debug("storeメソッドが呼び出されました。item_id: {$item_id}");

        try {
            $item = Item::findOrFail($item_id);
            Log::debug("商品情報を取得しました。", ['item' => $item]);

            if ($item->sold) {
                Log::debug("商品はすでに売り切れです。", ['item_id' => $item_id]);
                return redirect()->route('items.show', $item_id)->with('error', 'この商品はすでに売り切れです。');
            }

            // ユーザーが入力した支払い方法（たとえば「 カード払い 」）の文字列を取り出し、
            // 前後にあるいらない空白（スペース）を消して、きれいにする
            $trimmedPaymentMethod = trim($request->input('payment_method'));
            Log::debug("支払い方法を取得しました。", ['payment_method' => $trimmedPaymentMethod]);

            // 購入レコード
            Purchase::create([
                'user_id' => auth()->id(),
                'item_id' => $item->id,
                'postal_code' => $request->input('postal_code'),
                'address' => $request->input('address'),
                'building' => $request->input('building'),
                'payment_method' => $trimmedPaymentMethod,
            ]);
            Log::debug("購入レコードを作成しました。");

            // 取引テーブルに「新規取引」レコードを追加（ここが超重要！！）
            \App\Models\Trade::create([
                'item_id'   => $item->id,
                'seller_id' => $item->user_id,       // 出品者（アイテム作成者）
                'buyer_id'  => auth()->id(),         // 購入者（今のユーザー）
                'is_completed' => false,             // 取引はまだ未完了
            ]);
            Log::debug("取引レコードを作成しました。");

            // 商品の状態を「sold」に
            $item->update(['sold' => true]);
            Log::debug("商品状態を更新しました。", ['item_id' => $item_id]);

            return redirect()->route('items.index');
        } catch (\Exception $e) {
            Log::error("購入処理に失敗しました。", ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', '購入処理に失敗しました。');
        }
    }

    /**
     * Stripe 決済ページへリダイレクト
     */
    public function checkout($item_id)
    {
        Log::debug("checkoutメソッドが呼び出されました。item_id: {$item_id}");

        try {
            // StripeのAPIキー設定（Stripeという決済サービスを使うための「鍵」を設定します）
            // .envファイルに書かれている「STRIPE_SECRET_KEY」という秘密のキーを取り出します。
            // このキーは、Stripeに「このアプリは本物ですよ」と伝えるために使います。
            $stripeKey = env('STRIPE_SECRET_KEY');
            Log::debug("Stripe APIキーを設定しました。");

            // Stripeに「このアプリは本物です」と証明するために、さっき取り出した秘密のキーをセットします。
            // これをしないと、Stripeはこのアプリのリクエストを受け付けてくれません。
            Stripe::setApiKey($stripeKey);

            // 商品情報取得（どの商品を購入しようとしているのか、データベースから探します）
            // $item_id はURLから渡される商品IDです。
            // findOrFailは、商品が見つからなかった場合、自動で404エラー（ページが見つからない）を出してくれます。
            $item = Item::findOrFail($item_id);
            Log::debug("商品情報を取得しました。", ['item' => $item]);

            // ドメイン設定（Stripeから購入完了後に戻ってくるURLの「住所」です）
            // .envファイルにある APP_URL（このアプリのURL）を取り出します。
            // もし見つからない場合は「http://localhost（自分のパソコン）」を使います。
            // たとえば本番では https://furima-app.com などのURLになります。
            $YOUR_DOMAIN = env('APP_URL', 'http://localhost');
            Log::debug("ドメイン情報を設定しました。", ['domain' => $YOUR_DOMAIN]);

            // 画像URLの処理

            // 商品の画像パス（$item->image）が「すでにインターネットで使えるURL形式かどうか」を調べる
            // たとえば "https://example.com/image.jpg" のような形式か？というチェック
            if (filter_var($item->image, FILTER_VALIDATE_URL)) {
                // すでにURL形式なら、そのまま使う（加工しなくてOK）
                $image_url = $item->image;
            } else {
                // URLじゃない場合（たとえば "item1.jpg" などのファイル名だけだった場合）は、
                // サーバー内の「storage」フォルダにある画像として扱い、URLを自動で作る
                // 例: "item1.jpg" → "http://あなたのサイト/storage/item1.jpg"
                $image_url = asset('storage/' . $item->image);
            }
            $image_url = str_replace('+', '%20', $image_url);
            Log::debug("商品画像URLを設定しました。", ['image_url' => $image_url]);

            // 通貨を「日本円（jpy）」に設定します。
            // Stripeでは、使う通貨によって金額の扱い方が違います。
            $currency = 'jpy';
            Log::debug("通貨を設定しました。", ['currency' => $currency]);

            // Stripeに渡す「unit_amount（単位付きの金額）」を計算します。
            // Stripeでは、多くの通貨（たとえばドルやユーロ）の場合、金額を100倍して渡す必要があります。
            // たとえば「$10.00（10ドル）」なら「1000」を渡します（Stripeが小数点を扱わないため）
            // でも、日本円（jpy）は最初から整数（小数点なし）なので、そのままでOKです。

            // つまり：
            // - 通貨が「jpy（日本円）」だったら、price（価格）をそのまま使う
            // - それ以外の通貨だったら、price × 100 にして渡す
            $unit_amount = ($currency === 'jpy') ? $item->price : $item->price * 100;
            Log::debug("金額を設定しました。", ['unit_amount' => $unit_amount]);

            // StripeのCheckoutセッションを作成（ユーザーがクレジットカードで支払うためのページ）
            $checkout_session = Session::create([

                // 支払い方法の指定。今回はクレジットカード（'card'）のみ使えるようにする。
                'payment_method_types' => ['card'],

                // 商品情報の設定（1つの商品を売る設定）
                'line_items' => [[
                    // 値段や通貨など、金額に関するデータ
                    'price_data' => [
                        // お金の種類を設定（'jpy' は日本円のこと）
                        'currency' => $currency,

                        // 商品に関するデータ（名前や画像など）
                        'product_data' => [
                            // 商品名を表示（例：スニーカー）
                            'name' => $item->name,

                            // 商品の画像を表示（URL形式で指定）
                            'images' => [$image_url],
                        ],

                        // 商品の価格を設定（単位は「円」）
                        // Stripeではドルなどは「セント単位」で指定するが、日本円の場合はそのままでOK
                        'unit_amount' => $unit_amount,
                    ],

                    // 商品の数量（ここでは「1個」買うという意味）
                    'quantity' => 1,
                ]],

                // 支払い方法のモード（「購入モード」で実際にお金を支払う設定）
                'mode' => 'payment',

                // 支払いが成功したときにリダイレクトするURL（購入完了ページに戻る）
                'success_url' => $YOUR_DOMAIN . '/purchase/complete/' . $item_id,

                // 支払いを途中でキャンセルしたときに戻るURL（キャンセルページに移動）
                'cancel_url' => $YOUR_DOMAIN . '/cancel',
            ]);
            Log::debug("Stripe Checkoutセッションを作成しました。", ['checkout_session' => $checkout_session]);

            // Stripeから作られた支払いページのURLにリダイレクト（ユーザーの画面がStripeの支払い画面に切り替わる）
            return redirect($checkout_session->url);
        } catch (\Exception $e) {
            Log::error("決済画面への遷移に失敗しました。", ['error' => $e->getMessage()]);
            return redirect()->route('purchase.show', ['item_id' => $item_id])
                ->with('error', '決済画面への遷移に失敗しました。');
        }
    }

    // Stripe支払い後に呼び出される処理（storeのロジック再利用）
    public function complete($item_id)
    {
        Log::debug("completeメソッドが呼び出されました。item_id: {$item_id}");

        try {
            // 商品を取得
            $item = Item::findOrFail($item_id);
            Log::debug("商品情報を取得しました。", ['item' => $item]);

            // すでに売り切れならエラーで戻す
            if ($item->sold) {
                Log::debug("商品はすでに売り切れです。", ['item_id' => $item_id]);
                return redirect()->route('items.show', $item_id)->with('error', 'この商品はすでに売り切れです。');
            }

            // 現在のログインユーザー取得（購入者）
            $user = auth()->user();
            Log::debug("ログインユーザー情報を取得しました。", ['user' => $user]);

            // 購入履歴を作成
            Purchase::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'postal_code' => $user->postal_code,
                'address' => $user->address,
                'building' => $user->building,
                'payment_method' => 'カード払い', // ★固定
            ]);
            Log::debug("購入履歴を作成しました。");

            // 商品を売り切れ状態に変更
            $item->update(['sold' => true]);
            Log::debug("商品状態を更新しました。", ['item_id' => $item_id]);

            // -------------------------------------------
            // ここから追加：取引テーブルに新規取引レコードを作成する処理
            \App\Models\Trade::create([
                'item_id'      => $item->id,
                'seller_id'    => $item->user_id,  // 出品者
                'buyer_id'     => $user->id,       // 購入者
                'is_completed' => false,           // 取引は未完了
            ]);
            Log::debug("取引レコードを作成しました。");
            // -------------------------------------------

            // 購入完了メッセージでトップへリダイレクト
            return redirect()->route('items.index')->with('success', '購入が完了しました');
        } catch (\Exception $e) {
            Log::error("購入処理に失敗しました。", ['error' => $e->getMessage()]);
            // エラー時は商品詳細へ戻りエラーメッセージ
            return redirect()->route('items.show', $item_id)->with('error', '購入処理に失敗しました');
        }
    }
}
