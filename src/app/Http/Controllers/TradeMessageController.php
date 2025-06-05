<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TradeMessageRequest;
use Illuminate\Support\Facades\Auth; // ログイン中のユーザーを取得するため
use Illuminate\Support\Facades\Log;  // 追加: ログ出力用
use App\Models\Trade;                // 取引データを使うため
use App\Models\TradeMessage;         // チャット（メッセージ）データを使うため

class TradeMessageController extends Controller
{
    /**
     * チャットメッセージを新しく投稿するメソッド
     * URL例: /trade-chat/{trade_id}/message
     * メソッド: POST
     */
    public function store(TradeMessageRequest $request, $trade_id)
    {
        Log::debug("[store] メッセージ投稿処理開始: trade_id={$trade_id}");

        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();
        Log::debug("[store] ログインユーザーID={$user->id}");

        // 2. この取引データをデータベースから探す
        $trade = Trade::findOrFail($trade_id);
        Log::debug("[store] 取引データ取得成功: trade_id={$trade->id}");

        // 3. 入力されたメッセージ本文を取得する
        $body = $request->input('body'); // フォーム名が 'body' だと仮定
        Log::debug("[store] 入力メッセージ本文: " . ($body !== null ? mb_substr($body, 0, 100) : 'NULL'));

        // [追加] 入力保持（失敗時に入力を復元するため）
        session(['chat_body_' . $trade_id => $body]);

        // 4. 入力チェック（空ならエラー）
        if (empty($body)) {
            Log::debug("[store] メッセージ本文が空です。処理中断。");
            return redirect()->back()->with('error', 'メッセージを入力してください。');
        }

        // --- ここから画像アップロード処理追加 ---
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('trade_messages', 'public');
            Log::debug("[store] 画像アップロード成功: path={$imagePath}");
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
        Log::debug("[store] メッセージ保存成功: message_id={$message->id}");

        // [追加] 投稿成功したら入力保持データをクリア
        session()->forget('chat_body_' . $trade_id);

        // 6. チャット画面へ戻す（成功メッセージ付き）
        Log::debug("[store] 処理終了。チャット画面へリダイレクト。");
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
        Log::debug("[update] メッセージ編集処理開始: message_id={$message_id}");

        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();
        Log::debug("[update] ログインユーザーID={$user->id}");

        // 2. 編集したいチャットメッセージを探す
        $message = TradeMessage::findOrFail($message_id);
        Log::debug("[update] 編集対象メッセージ取得成功: message_id={$message->id}, user_id={$message->user_id}");

        // 3. このメッセージが自分のものでなければエラー
        if ($message->user_id !== $user->id) {
            Log::debug("[update] 編集権限なし: ログインユーザーID={$user->id} メッセージ所有者ID={$message->user_id}");
            return redirect()->back()->with('error', '自分のメッセージだけ編集できます。');
        }

        // 4. 入力された新しい本文を取得
        $body = $request->input('body');
        Log::debug("[update] 入力新本文: " . ($body !== null ? mb_substr($body, 0, 100) : 'NULL'));
        if (empty($body)) {
            Log::debug("[update] 新本文が空です。処理中断。");
            return redirect()->back()->with('error', 'メッセージを入力してください。');
        }

        // 5. メッセージを更新
        $message->body = $body;
        $message->save();
        Log::debug("[update] メッセージ更新成功: message_id={$message->id}");

        // 6. チャット画面へ戻す
        Log::debug("[update] 処理終了。チャット画面へリダイレクト。");
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
        Log::debug("[destroy] メッセージ削除処理開始: message_id={$message_id}");

        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();
        Log::debug("[destroy] ログインユーザーID={$user->id}");

        // 2. 削除したいメッセージを探す
        $message = TradeMessage::findOrFail($message_id);
        Log::debug("[destroy] 削除対象メッセージ取得成功: message_id={$message->id}, user_id={$message->user_id}");

        // 3. このメッセージが自分のものでなければエラー
        if ($message->user_id !== $user->id) {
            Log::debug("[destroy] 削除権限なし: ログインユーザーID={$user->id} メッセージ所有者ID={$message->user_id}");
            return redirect()->back()->with('error', '自分のメッセージだけ削除できます。');
        }

        // 4. メッセージを削除
        $trade_id = $message->trade_id; // 削除前にIDを取得
        $message->delete();
        Log::debug("[destroy] メッセージ削除成功: message_id={$message_id}");

        // 5. チャット画面へ戻す
        Log::debug("[destroy] 処理終了。チャット画面へリダイレクト。");
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
        Log::debug("[markAsRead] 既読処理開始: trade_id={$trade_id}");

        // 1. 今ログインしているユーザーを取得
        $user = Auth::user();
        Log::debug("[markAsRead] ログインユーザーID={$user->id}");

        // 2. この取引の自分宛て未読メッセージだけを取り出す
        $unreadMessages = TradeMessage::where('trade_id', $trade_id)
            ->where('user_id', '!=', $user->id) // 自分以外が送った
            ->where('is_read', false)           // まだ未読
            ->get();
        Log::debug("[markAsRead] 未読メッセージ数: " . $unreadMessages->count());

        // 3. すべて「既読」にする
        foreach ($unreadMessages as $message) {
            $message->is_read = true;
            $message->save();
            Log::debug("[markAsRead] 既読に更新: message_id={$message->id}");
        }

        // 4. チャット画面へ戻す
        Log::debug("[markAsRead] 処理終了。チャット画面へリダイレクト。");
        return redirect()->route('trade.chat.show', $trade_id);
    }
}
