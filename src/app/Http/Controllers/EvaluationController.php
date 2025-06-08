<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Trade;
use App\Models\Evaluation;

class EvaluationController extends Controller
{
    /**
     * 評価フォームを表示するメソッド（モーダル画面など）
     * URL例: /trade/{trade_id}/evaluate
     * メソッド: GET
     */
    public function create($trade_id)
    {
        // 1. 今ログインしているユーザーを取得する
        $user = Auth::user();

        // 2. 指定された取引（trade_id）のデータをDBから探す
        $trade = Trade::with(['item', 'seller', 'buyer'])->findOrFail($trade_id);

        // 3. この取引に関係のない人は評価フォームを見せない（出品者or購入者のみ）
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            return redirect()->back()->with('error', 'この取引の評価をする権限がありません。');
        }

        // 4. すでに評価していたら（同じ人が同じ取引で評価済みかどうかを調べる）
        $alreadyEvaluated = Evaluation::where('trade_id', $trade->id)
            ->where('evaluator_id', $user->id)
            ->exists();

        if ($alreadyEvaluated) {
            // すでに評価済みならメッセージを表示して元の画面に戻す
            return redirect()->back()->with('error', 'この取引はすでに評価済みです。');
        }

        // 5. 評価フォームの画面を表示し、必要なデータを渡す
        return view('evaluation.create', [
            'trade' => $trade, // 取引のデータ（商品・出品者・購入者も含む）
            'user' => $user,   // 今ログインしているユーザー
        ]);
    }

    /**
     * 評価を送信して保存するメソッド
     * URL例: /trade/{trade_id}/evaluate
     * メソッド: POST
     */
    public function store(Request $request, $trade_id)
    {
        $user = Auth::user();

        // 評価を送る取引データ取得
        $trade = Trade::with(['item', 'seller', 'buyer', 'evaluations'])->findOrFail($trade_id);

        // 権限チェックなどは省略（既存通り）

        // 重複評価チェック
        $alreadyEvaluated = Evaluation::where('trade_id', $trade->id)
            ->where('evaluator_id', $user->id)
            ->exists();

        if ($alreadyEvaluated) {
            return redirect()->back()->with('error', 'この取引はすでに評価済みです。');
        }

        // 評価点数受け取り
        $score = $request->input('score');

        $evaluated_id = ($user->id === $trade->seller_id)
            ? $trade->buyer_id
            : $trade->seller_id;

        // 評価登録
        Evaluation::create([
            'trade_id'      => $trade->id,
            'evaluator_id'  => $user->id,
            'evaluated_id'  => $evaluated_id,
            'score'         => $score,
        ]);

        // --- 購入者が評価した場合のみ通知を出品者へ送信 ---
        if ($user->id === $trade->buyer_id) {
            try {
                $trade->seller->notify(new \App\Notifications\TradeCompletedNotification($trade, $user));
            } catch (\Exception $e) {
            }
        }

        return redirect()->route('items.index')
            ->with('success', '評価が完了しました！');
    }
}
