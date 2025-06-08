<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 今ログインしている人の情報を使うため
use Illuminate\Support\Facades\Log;  // ログ出力用
use App\Models\Trade;                // 取引データを使うため
use App\Models\Evaluation;           // 評価データを使うため

class EvaluationController extends Controller
{
    /**
     * 評価フォームを表示するメソッド（モーダル画面など）
     * URL例: /trade/{trade_id}/evaluate
     * メソッド: GET
     */
    public function create($trade_id)
    {
        Log::debug("評価フォーム表示処理開始 - trade_id: {$trade_id}");

        // 1. 今ログインしているユーザーを取得する
        $user = Auth::user();
        Log::debug('ログインユーザー取得', ['user_id' => $user->id]);

        // 2. 指定された取引（trade_id）のデータをDBから探す
        $trade = Trade::with(['item', 'seller', 'buyer'])->findOrFail($trade_id);
        Log::debug('取引データ取得', ['trade_id' => $trade->id, 'seller_id' => $trade->seller_id, 'buyer_id' => $trade->buyer_id]);

        // 3. この取引に関係のない人は評価フォームを見せない（出品者or購入者のみ）
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            Log::debug('評価権限なしのユーザーがアクセス', ['user_id' => $user->id]);
            return redirect()->back()->with('error', 'この取引の評価をする権限がありません。');
        }

        // 4. すでに評価していたら（同じ人が同じ取引で評価済みかどうかを調べる）
        $alreadyEvaluated = Evaluation::where('trade_id', $trade->id)
            ->where('evaluator_id', $user->id)
            ->exists();

        Log::debug('評価済み確認', ['already_evaluated' => $alreadyEvaluated]);

        if ($alreadyEvaluated) {
            Log::debug('既に評価済みの取引にアクセス', ['trade_id' => $trade->id, 'user_id' => $user->id]);
            // すでに評価済みならメッセージを表示して元の画面に戻す
            return redirect()->back()->with('error', 'この取引はすでに評価済みです。');
        }

        // 5. 評価フォームの画面を表示し、必要なデータを渡す
        Log::debug('評価フォーム表示へ遷移', ['trade_id' => $trade->id, 'user_id' => $user->id]);
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
        Log::debug("1. 評価保存処理開始 - trade_id: {$trade_id}");

        $user = Auth::user();
        Log::debug('2. ログインユーザー取得', ['user_id' => $user->id]);

        // 評価を送る取引データ取得
        $trade = Trade::with(['item', 'seller', 'buyer', 'evaluations'])->findOrFail($trade_id);
        Log::debug('3. 取引データ取得', [
            'trade_id' => $trade->id,
            'seller_id' => $trade->seller_id,
            'buyer_id' => $trade->buyer_id,
            'evaluations_count' => $trade->evaluations->count(),
        ]);

        // 権限チェックなどは省略（既存通り）

        // 重複評価チェック
        $alreadyEvaluated = Evaluation::where('trade_id', $trade->id)
            ->where('evaluator_id', $user->id)
            ->exists();
        Log::debug('4. 重複評価チェック', ['alreadyEvaluated' => $alreadyEvaluated]);

        if ($alreadyEvaluated) {
            Log::debug('5. 重複評価試行検出、処理中断');
            return redirect()->back()->with('error', 'この取引はすでに評価済みです。');
        }

        // 評価点数受け取り
        $score = $request->input('score');
        Log::debug('6. フォームからの評価点数受取', ['score' => $score]);

        $evaluated_id = ($user->id === $trade->seller_id)
            ? $trade->buyer_id
            : $trade->seller_id;
        Log::debug('7. 評価されるユーザーID決定', ['evaluated_id' => $evaluated_id]);

        // 評価登録
        Evaluation::create([
            'trade_id'      => $trade->id,
            'evaluator_id'  => $user->id,
            'evaluated_id'  => $evaluated_id,
            'score'         => $score,
        ]);
        Log::debug('8. 評価データ保存完了', [
            'trade_id' => $trade->id,
            'evaluator_id' => $user->id,
            'evaluated_id' => $evaluated_id,
            'score' => $score,
        ]);

        // --- 購入者が評価した場合のみ通知を出品者へ送信 ---
        if ($user->id === $trade->buyer_id) {
            Log::debug('9. 購入者が評価したため通知メール送信処理へ');
            try {
                $trade->seller->notify(new \App\Notifications\TradeCompletedNotification($trade, $user));
                Log::debug('10. 購入者評価→取引完了通知メール送信成功', ['seller_email' => $trade->seller->email]);
            } catch (\Exception $e) {
                Log::error('10. 購入者評価→取引完了通知メール送信失敗', ['error' => $e->getMessage()]);
            }
        } else {
            Log::debug('9. 出品者が評価したため、通知メール送信なし');
        }

        // 取引完了フラグは「双方評価済み」時にだけ立てたい場合は、ここでは立てない
        // もしくは即完了したいならここで $trade->is_completed = true; $trade->save();

        Log::debug('11. 評価完了、チャット画面へリダイレクト');
        return redirect()->route('trade.chat.show', $trade_id)
            ->with('success', '評価が完了しました！');
    }
}
