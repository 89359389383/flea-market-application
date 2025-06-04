<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 今ログインしている人を使うため
use App\Models\Trade;                // 取引のデータを使うため
use App\Models\TradeMessage;         // 取引のメッセージ（チャット）を使うため

class TradeController extends Controller
{
    /**
     * 取引チャット画面を表示するメソッド
     * URL例: /trade-chat/1
     * メソッド: GET
     */
    public function show($trade_id)
    {
        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();

        // 2. 取引ID（trade_id）で該当する取引データをデータベースから探す
        // (1) 'item'
        // itemはTradeモデルの「item()」リレーション（$trade->item）のことです。
        // この取引で売買している「商品」のデータを一緒に取得します。
        // 例：商品名、画像、価格など。
        //
        // (2) 'seller'
        // sellerはTradeモデルの「seller()」リレーション（$trade->seller）。
        // この取引の「出品者」（売った人）の情報を一緒に取得します。
        // 例：出品者の名前、プロフィール画像など。
        //
        // (3) 'buyer'
        // buyerはTradeモデルの「buyer()」リレーション（$trade->buyer）。
        // この取引の「購入者」（買った人）の情報を一緒に取得します。
        // 例：購入者の名前、住所など。
        //
        // (4) 'messages.user'
        // messagesはTradeモデルの「messages()」リレーション（$trade->messages）。
        // → この取引でやり取りされた「チャットメッセージ」一覧を取得。
        // さらにその後ろの.userは、「そのメッセージを書き込んだユーザー情報」も一緒に取得します。
        // つまり「チャット一覧＋その発言者（誰が何を言ったか）」まで全部を一気に読み込
        $trade = Trade::with(['item', 'seller', 'buyer', 'messages.user'])->findOrFail($trade_id);
        // ↑ with()で「商品」「出品者」「購入者」「メッセージ＆その送信者」も一緒に取得

        // 3. この取引が今のユーザーに関係があるか確認する
        // （出品者か購入者でない場合は見せない）
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            // もし自分が出品者でも購入者でもなかったら前のページへ戻す
            return redirect()->back()->with('error', 'この取引チャットを見る権限がありません。');
        }

        // 4. 取引のチャットメッセージ一覧を新しい順（または古い順）で取得
        $messages = $trade->messages()->orderBy('created_at', 'asc')->get();
        // ↑ チャットが時系列で並ぶように「古い順」にしている

        // 5. ビュー（画面）を表示し、必要なデータを渡す
        // ここでは「trade/chat.blade.php」という画面テンプレートを使うイメージ
        return view('trade.chat', [
            'trade' => $trade,        // 取引のデータ（商品・出品者・購入者含む）
            'messages' => $messages,  // チャットメッセージの一覧
            'user' => $user,          // 今ログインしているユーザー
        ]);
    }
}
