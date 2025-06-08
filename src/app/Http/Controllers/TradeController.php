<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth; // 今ログインしている人を使うため
use Illuminate\Support\Facades\Log;  // ログ出力のため追加
use App\Models\Trade;                // 取引のデータを使うため

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
        if (!$user) {
            Log::warning("未ログインユーザーのアクセス試行");
            return redirect()->route('login')->with('error', 'ログインが必要です。');
        }
        Log::debug("現在のログインユーザー: id={$user->id}, name={$user->name}");

        // 2. 取引IDで該当取引データをwithでリレーションとともに取得
        try {
            $trade = Trade::with(['item', 'seller', 'buyer', 'messages.user', 'evaluations'])->findOrFail($trade_id);
            Log::debug("取得した取引データ:", [
                'trade_id' => $trade->id,
                'item_id' => $trade->item->id ?? null,
                'seller_id' => $trade->seller->id ?? null,
                'buyer_id' => $trade->buyer->id ?? null,
                'messages_count' => $trade->messages->count(),
                'evaluations_count' => $trade->evaluations->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("取引データ取得エラー - trade_id: {$trade_id} - " . $e->getMessage());
            return redirect()->back()->with('error', '該当する取引が見つかりません。');
        }

        // 3. ユーザーが出品者または購入者か確認
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            Log::warning("アクセス拒否: ユーザーは取引の出品者でも購入者でもない", [
                'user_id' => $user->id,
                'seller_id' => $trade->seller_id,
                'buyer_id' => $trade->buyer_id,
            ]);
            return redirect()->back()->with('error', 'この取引チャットを見る権限がありません。');
        }
        Log::debug("アクセス許可: ユーザーは取引の当事者である");

        // 4. チャットメッセージ一覧を作成日時昇順で取得
        try {
            $messages = $trade->messages()->with('user')->orderBy('created_at', 'asc')->get();
            Log::debug("メッセージ取得件数: " . $messages->count());
        } catch (\Exception $e) {
            Log::error("メッセージ取得エラー - trade_id: {$trade->id} - " . $e->getMessage());
            return redirect()->back()->with('error', 'メッセージの取得に失敗しました。');
        }

        // 5. 未読メッセージを既読にする処理
        $updatedCount = 0;
        foreach ($messages as $message) {
            Log::debug("メッセージ確認中: message_id={$message->id}, user_id={$message->user_id}, is_read=" . ($message->is_read ? 'true' : 'false'));
            if ($message->user_id !== $user->id && !$message->is_read) {
                $message->is_read = true;
                try {
                    $message->save();
                    $updatedCount++;
                    Log::debug("未読メッセージを既読に更新成功: message_id={$message->id}, user_id={$message->user_id}");
                } catch (\Exception $e) {
                    Log::error("未読メッセージの既読更新失敗: message_id={$message->id} - " . $e->getMessage());
                }
            }
        }
        Log::debug("未読メッセージの既読更新件数: {$updatedCount}");

        // 6. パートナー（相手ユーザー）を決定
        $partner = ($trade->seller_id === $user->id) ? $trade->buyer : $trade->seller;
        Log::debug("パートナー情報: id={$partner->id}, name={$partner->name}");

        // 7. サイドバー用、関係する全取引取得
        try {
            $other_trades = Trade::with(['item', 'messages'])
                ->where(function ($q) use ($user) {
                    $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id);
                })->get();
            Log::debug("サイドバー用取引一覧取得件数: {$other_trades->count()}");
        } catch (\Exception $e) {
            Log::error("サイドバー用取引一覧取得エラー - user_id: {$user->id} - " . $e->getMessage());
            $other_trades = collect(); // 空コレクション返す
        }

        // 8. 追加：評価済みかどうか判定
        $alreadyEvaluated = $trade->evaluations->where('evaluator_id', $user->id)->first();
        $partner_id = ($user->id === $trade->seller_id) ? $trade->buyer_id : $trade->seller_id;
        $partnerEvaluated = $trade->evaluations->where('evaluator_id', $partner_id)->first();

        // 9. すでに双方評価済みかつ取引完了フラグ未立ての場合は立てる
        if ($alreadyEvaluated && $partnerEvaluated && !$trade->is_completed) {
            $trade->is_completed = true;
            $trade->save();
            Log::debug("双方評価済みのため取引完了フラグを立てました。trade_id: {$trade->id}");
        }

        // 10. ビューを返す直前のログ
        Log::debug("ビュー呼び出し準備完了: trade.chat", [
            'trade_id' => $trade->id,
            'messages_count' => $messages->count(),
            'user_id' => $user->id,
            'partner_id' => $partner->id,
            'other_trades_count' => $other_trades->count(),
            'alreadyEvaluated' => $alreadyEvaluated ? true : false,
            'partnerEvaluated' => $partnerEvaluated ? true : false,
            'is_completed' => $trade->is_completed,
        ]);

        // 11. ビューへ渡す
        return view('trade.chat', [
            'trade' => $trade,
            'messages' => $messages,
            'partner' => $partner,
            'other_trades' => $other_trades,
            'user' => $user,
            'alreadyEvaluated' => $alreadyEvaluated,
            'partnerEvaluated' => $partnerEvaluated,
        ]);
    }
}
