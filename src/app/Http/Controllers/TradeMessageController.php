<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TradeMessageRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Trade;
use App\Models\TradeMessage;

class TradeMessageController extends Controller
{
    /**
     * チャットメッセージを新しく投稿するメソッド
     * URL例: /trade-chat/{trade_id}/message
     * メソッド: POST
     */
    public function store(TradeMessageRequest $request, $trade_id)
    {
        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();

        // 2. この取引データをデータベースから探す
        $trade = Trade::findOrFail($trade_id);

        // 3. 入力されたメッセージ本文を取得する
        $body = $request->input('body'); // フォーム名が 'body' だと仮定

        // [追加] 入力保持（失敗時に入力を復元するため）
        session(['chat_body_' . $trade_id => $body]);

        // 4. 入力チェック（空ならエラー）
        if (empty($body)) {
            return redirect()->back()->with('error', 'メッセージを入力してください。');
        }

        // --- ここから画像アップロード処理追加 ---
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('trade_messages', 'public');
        }
        // --- ここまで画像アップロード処理 ---

        // 5. 新しいチャットメッセージをデータベースに保存
        $message = TradeMessage::create([
            'trade_id' => $trade->id,    // どの取引か
            'user_id' => $user->id,      // 誰が送ったか
            'body' => $body,             // 本文
            'image_path' => $imagePath,  // 画像パス（nullの場合もあり）
            'is_read' => false,          // 既読フラグ（新規は未読）
        ]);

        // [追加] 投稿成功したら入力保持データをクリア
        session()->forget('chat_body_' . $trade_id);

        // 6. チャット画面へ戻す（成功メッセージ付き）
        return redirect()->route('trade.chat.show', $trade_id)
            ->with('success', 'メッセージを送信しました！');
    }

    /**
     * チャットメッセージを編集するメソッド
     * URL例: /trade-message/{message_id}
     * メソッド: PUT
     */
    public function update(Request $request, $message_id)
    {
        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();

        // 2. 編集したいチャットメッセージを探す
        $message = TradeMessage::findOrFail($message_id);

        // 3. このメッセージが自分のものでなければエラー
        if ($message->user_id !== $user->id) {
            return redirect()->back()->with('error', '自分のメッセージだけ編集できます。');
        }

        // 4. 入力された新しい本文を取得
        $body = $request->input('body');
        if (empty($body)) {
            return redirect()->back()->with('error', 'メッセージを入力してください。');
        }

        // 5. メッセージを更新
        $message->body = $body;
        $message->save();

        // 6. チャット画面へ戻す
        return redirect()->route('trade.chat.show', $message->trade_id)
            ->with('success', 'メッセージを編集しました！');
    }

    /**
     * チャットメッセージを削除するメソッド
     * URL例: /trade-message/{message_id}
     * メソッド: DELETE
     */
    public function destroy($message_id)
    {
        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();

        // 2. 削除したいメッセージを探す
        $message = TradeMessage::findOrFail($message_id);

        // 3. このメッセージが自分のものでなければエラー
        if ($message->user_id !== $user->id) {
            return redirect()->back()->with('error', '自分のメッセージだけ削除できます。');
        }

        // 4. メッセージを削除
        $trade_id = $message->trade_id; // 削除前にIDを取得
        $message->delete();

        // 5. チャット画面へ戻す
        return redirect()->route('trade.chat.show', $trade_id)
            ->with('success', 'メッセージを削除しました。');
    }

    /**
     * チャットメッセージを既読にするメソッド（新着通知解除などに使う）
     * URL例: /trade-chat/{trade_id}/read
     * メソッド: POST
     */
    public function markAsRead($trade_id)
    {
        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();

        // 2. この取引の自分宛て未読メッセージだけを取り出す
        $unreadMessages = TradeMessage::where('trade_id', $trade_id)
            ->where('user_id', '!=', $user->id) // 自分以外が送った
            ->where('is_read', false)           // まだ未読
            ->get();

        // 3. すべて「既読」にする
        foreach ($unreadMessages as $message) {
            $message->is_read = true;
            $message->save();
        }

        // 4. チャット画面へ戻す
        return redirect()->route('trade.chat.show', $trade_id);
    }
}
