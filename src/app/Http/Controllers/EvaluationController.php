<?php

namespace App\Http\Controllers;

use App\Http\Requests\TradeMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 今ログインしている人の情報を使うため
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
        // ここでは「evaluation/create.blade.php」という画面テンプレートを使うイメージ
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
        // 1. 今ログインしているユーザー情報を取得する
        $user = Auth::user();

        // 2. 指定された取引IDに対応する取引情報を、商品・出品者・購入者の情報も合わせて取得する
        $trade = Trade::with(['item', 'seller', 'buyer'])->findOrFail($trade_id);

        // 3. ログインユーザーがその取引の出品者か購入者かを確認し、どちらでもなければエラーを返して前のページに戻る
        if ($trade->seller_id !== $user->id && $trade->buyer_id !== $user->id) {
            return redirect()->back()->with('error', 'この取引の評価をする権限がありません。');
        }

        // 4. すでに同じユーザーがこの取引に対して評価をしているかをデータベースで確認する
        $alreadyEvaluated = Evaluation::where('trade_id', $trade->id)
            ->where('evaluator_id', $user->id)
            ->exists();

        // 5. もし評価済みならエラーメッセージを返して前のページに戻す
        if ($alreadyEvaluated) {
            return redirect()->back()->with('error', 'この取引はすでに評価済みです。');
        }

        // 6. バリデーション済みのデータを安全に取得する（TradeEvaluationRequestでチェック済み）
        $validated = $request->validated();

        // 7. 評価される人のIDを決める。ログインユーザーが出品者なら購入者を、購入者なら出品者を評価する
        $evaluated_id = ($user->id === $trade->seller_id)
            ? $trade->buyer_id
            : $trade->seller_id;

        // 8. 新しいEvaluationモデルのインスタンスを作成し、必要なデータをセットする
        Evaluation::create([
            'trade_id'      => $trade->id,
            'evaluator_id'  => $user->id,
            'evaluated_id'  => $evaluated_id,
            'score'         => $validated['score'],
        ]);

        // 9. 評価が完了したことを伝える成功メッセージを付けて、取引チャット画面にリダイレクトする
        return redirect()->route('trade.chat.show', $trade->id)
            ->with('success', '取引の評価を登録しました。');
    }
}
