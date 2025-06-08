<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth; // 今ログインしている人を使うため
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
        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'ログインが必要です。');
        }

        // 2. 取引IDで該当取引データをwithでリレーションとともに取得
        try {
            $trade = Trade::with(['item', 'seller', 'buyer', 'messages.user', 'evaluations'])->findOrFail($trade_id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '該当する取引が見つかりません。');
        }

        // 3. ユーザーが出品者または購入者か確認
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            return redirect()->back()->with('error', 'この取引チャットを見る権限がありません。');
        }

        // 4. チャットメッセージ一覧を作成日時昇順で取得
        try {
            $messages = $trade->messages()->with('user')->orderBy('created_at', 'asc')->get();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'メッセージの取得に失敗しました。');
        }

        // 5. 未読メッセージを既読にする処理
        $updatedCount = 0;
        foreach ($messages as $message) {
            if ($message->user_id !== $user->id && !$message->is_read) {
                $message->is_read = true;
                try {
                    $message->save();
                    $updatedCount++;
                } catch (\Exception $e) {
                    // 既読更新失敗時も処理継続
                }
            }
        }

        // 6. パートナー（相手ユーザー）を決定
        $partner = ($trade->seller_id === $user->id) ? $trade->buyer : $trade->seller;

        // 7. サイドバー用、関係する全取引取得
        try {
            $other_trades = Trade::with(['item', 'messages'])
                ->where(function ($q) use ($user) {
                    $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id);
                })->get();
        } catch (\Exception $e) {
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
        }

        // 10. ビューへ渡す
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
