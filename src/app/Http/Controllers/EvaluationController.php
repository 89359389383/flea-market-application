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
        Log::debug("評価保存処理開始 - trade_id: {$trade_id}");

        // 1. 今ログインしているユーザー情報を取得する
        $user = Auth::user();
        Log::debug('ログインユーザー取得', ['user_id' => $user->id]);

        // 2. 指定された取引IDに対応する取引情報を取得する
        $trade = Trade::with(['item', 'seller', 'buyer'])->findOrFail($trade_id);
        Log::debug('取引データ取得', ['trade_id' => $trade->id, 'seller_id' => $trade->seller_id, 'buyer_id' => $trade->buyer_id]);

        // 3. ログインユーザーがその取引の出品者か購入者かを確認し、どちらでもなければエラー
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            Log::debug('評価権限なしのユーザーによる評価試行', ['user_id' => $user->id]);
            return redirect()->back()->with('error', 'この取引の評価をする権限がありません。');
        }

        // 4. すでに評価している場合は重複登録を防ぐ
        $alreadyEvaluated = Evaluation::where('trade_id', $trade->id)
            ->where('evaluator_id', $user->id)
            ->exists();

        Log::debug('評価済み確認', ['already_evaluated' => $alreadyEvaluated]);

        if ($alreadyEvaluated) {
            Log::debug('重複評価試行', ['trade_id' => $trade->id, 'user_id' => $user->id]);
            return redirect()->back()->with('error', 'この取引はすでに評価済みです。');
        }

        // 5. フォームから評価点数(score)だけ受け取って保存
        $score = $request->input('score'); // name="score" でフォーム送信されていることが前提
        Log::debug('フォームからのスコア受取', ['score' => $score]);

        // 6. 評価される人のIDを決める
        $evaluated_id = ($user->id === $trade->seller_id)
            ? $trade->buyer_id
            : $trade->seller_id;
        Log::debug('評価されるユーザーID決定', ['evaluated_id' => $evaluated_id]);

        // 7. 新しいEvaluationモデルのインスタンスを作成し、必要なデータをセットして保存
        Evaluation::create([
            'trade_id'      => $trade->id,
            'evaluator_id'  => $user->id,
            'evaluated_id'  => $evaluated_id,
            'score'         => $score,
        ]);
        Log::debug('評価データ保存完了', [
            'trade_id' => $trade->id,
            'evaluator_id' => $user->id,
            'evaluated_id' => $evaluated_id,
            'score' => $score,
        ]);

        // 8. 取引完了フラグを立てて保存
        $trade->is_completed = true;
        $trade->save();
        Log::debug('取引完了フラグを立てて保存完了', ['trade_id' => $trade->id]);

        // 9. 評価が完了したことを伝えるメッセージを付けて、商品一覧画面（トップページ）にリダイレクト
        Log::debug('評価完了、商品一覧ページへリダイレクト', ['user_id' => $user->id]);
        return redirect()->route('items.index')
            ->with('success', '取引の評価を登録し、取引を完了しました。');
    }
}
