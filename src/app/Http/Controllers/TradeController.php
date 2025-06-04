<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 今ログインしている人を使うため
use Illuminate\Support\Facades\Log;  // ログ出力のため追加
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
        Log::debug("TradeController@show 開始 - trade_id: {$trade_id}");

        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();
        Log::debug("現在のログインユーザー: id={$user->id}, name={$user->name}");

        // 2. 取引IDで該当取引データをwithでリレーションとともに取得
        $trade = Trade::with(['item', 'seller', 'buyer', 'messages.user'])->findOrFail($trade_id);
        Log::debug("取得した取引データ:", [
            'trade_id' => $trade->id,
            'item_id' => $trade->item->id ?? null,
            'seller_id' => $trade->seller->id ?? null,
            'buyer_id' => $trade->buyer->id ?? null,
            'messages_count' => $trade->messages->count(),
        ]);

        // 3. ユーザーが出品者または購入者か確認
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            Log::debug("アクセス拒否: ユーザーは取引の出品者でも購入者でもない", [
                'user_id' => $user->id,
                'seller_id' => $trade->seller_id,
                'buyer_id' => $trade->buyer_id,
            ]);
            return redirect()->back()->with('error', 'この取引チャットを見る権限がありません。');
        }
        Log::debug("アクセス許可: ユーザーは取引の当事者である");

        // 4. チャットメッセージ一覧を作成日時昇順で取得
        $messages = $trade->messages()->orderBy('created_at', 'asc')->get();
        Log::debug("メッセージ取得件数: " . $messages->count());

        // 5. 未読メッセージを既読にする処理
        $updatedCount = 0;
        foreach ($messages as $message) {
            if ($message->user_id !== $user->id && !$message->is_read) {
                $message->is_read = true;
                $message->save();
                $updatedCount++;
                Log::debug("未読メッセージを既読に更新: message_id={$message->id}, user_id={$message->user_id}");
            }
        }
        Log::debug("未読メッセージの既読更新件数: {$updatedCount}");

        // 6. ビューを返す直前のログ
        Log::debug("ビュー呼び出し: trade.chat", [
            'trade_id' => $trade->id,
            'messages_count' => $messages->count(),
            'user_id' => $user->id,
        ]);

        return view('trade.chat', [
            'trade' => $trade,
            'messages' => $messages,
            'user' => $user,
        ]);
    }
}
