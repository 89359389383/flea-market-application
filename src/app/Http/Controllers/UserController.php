<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Logファサード
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Item;
use App\Models\Trade;
use App\Http\Requests\ProfileRequest;

class UserController extends Controller
{
    /**
     * ユーザーのプロフィール情報を表示するメソッド
     * URL: /mypage
     * メソッド: GET (認証必須)
     */
    public function show(Request $request)
    {
        Log::debug('[UserController@show] メソッド開始');

        $user = auth()->user();
        Log::debug('[UserController@show] ログインユーザー取得', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'email' => $user->email,
        ]);

        $tab = $request->query('page', 'sell');
        Log::debug('[UserController@show] クエリパラメータ page=', ['page' => $tab]);

        // 評価平均と評価数
        $average_score = $user->evaluationsReceived()->avg('score');
        $evaluations_count = $user->evaluationsReceived()->count();
        Log::debug('[UserController@show] 評価平均と評価数取得', [
            'average_score' => $average_score,
            'evaluations_count' => $evaluations_count,
        ]);

        // 取引中商品のトレード一覧を取得（未読メッセージ数計算用）
        $trades_for_unread = Trade::with('messages')
            ->where('is_completed', false)
            ->where(function ($query) use ($user) {
                $query->where('seller_id', $user->id)
                    ->orWhere('buyer_id', $user->id);
            })
            ->get();

        Log::debug('[UserController@show] 取引中商品の取得（未読計算用）', [
            'count' => $trades_for_unread->count(),
            'trade_ids' => $trades_for_unread->pluck('id')->toArray(),
        ]);

        // 未読メッセージ総数計算
        $unread_messages_total = 0;
        foreach ($trades_for_unread as $trade) {
            $unread_count = $trade->messages()
                ->where('user_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
            Log::debug('[UserController@show] 取引ごとの未読メッセージ数', [
                'trade_id' => $trade->id,
                'unread_count' => $unread_count,
            ]);
            $unread_messages_total += $unread_count;
        }
        Log::debug('[UserController@show] 未読メッセージ合計', ['unread_messages_total' => $unread_messages_total]);

        if ($tab === 'trading') {
            Log::debug('[UserController@show] 取引中商品の一覧を取得開始');

            $trades = Trade::with(['item', 'seller', 'buyer', 'messages'])
                ->where('is_completed', false)
                ->where(function ($query) use ($user) {
                    Log::debug('[UserController@show][Trade Query] 売り手か買い手がログインユーザー', ['user_id' => $user->id]);
                    $query->where('seller_id', $user->id)
                        ->orWhere('buyer_id', $user->id);
                })
                ->withMax('messages', 'created_at')
                ->orderByDesc(DB::raw('COALESCE(messages_max_created_at, trades.updated_at)'))
                ->get();

            Log::debug('[UserController@show] 取引中商品の取得完了', ['count' => $trades->count(), 'trade_ids' => $trades->pluck('id')->toArray()]);

            return view('user.show', [
                'user' => $user,
                'trades' => $trades,
                'tab' => $tab,
                'average_score' => $average_score,
                'evaluations_count' => $evaluations_count,
                'unread_messages_total' => $unread_messages_total,
            ]);
        }

        if ($tab === 'buy') {
            Log::debug('[UserController@show] 購入済み商品一覧を取得開始');

            $trades = Trade::with(['item'])
                ->where('is_completed', true)
                ->where('buyer_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->get();

            Log::debug('[UserController@show] 購入済み商品のTrade取得完了', ['count' => $trades->count(), 'trade_ids' => $trades->pluck('id')->toArray()]);

            $items = $trades->map(function ($trade) {
                Log::debug('[UserController@show][map] Tradeからitemを取得', ['trade_id' => $trade->id, 'item_id' => optional($trade->item)->id]);
                return $trade->item;
            });

            Log::debug('[UserController@show] 購入済み商品のItem変換完了', ['count' => $items->count(), 'item_ids' => $items->pluck('id')->toArray()]);

            return view('user.show', [
                'user' => $user,
                'tab' => $tab,
                'items' => $items,
                'average_score' => $average_score,
                'evaluations_count' => $evaluations_count,
                'unread_messages_total' => $unread_messages_total,
            ]);
        }

        Log::debug('[UserController@show] 出品商品一覧を取得開始');

        $items = Item::where('user_id', $user->id)->get();

        Log::debug('[UserController@show] 出品商品の取得完了', ['count' => $items->count(), 'item_ids' => $items->pluck('id')->toArray()]);

        return view('user.show', [
            'user' => $user,
            'tab' => $tab,
            'items' => $items,
            'average_score' => $average_score,
            'evaluations_count' => $evaluations_count,
            'unread_messages_total' => $unread_messages_total,
        ]);
    }

    /**
     * プロフィール編集ページを表示するメソッド
     * URL: /mypage/profile
     * メソッド: GET (認証必須)
     */
    public function edit()
    {
        Log::debug('[UserController@edit] メソッド開始');

        $user = auth()->user();
        Log::debug('[UserController@edit] ログインユーザー取得', ['user_id' => $user->id, 'user_name' => $user->name, 'email' => $user->email]);

        return view('user.edit', ['user' => $user]);
    }

    /**
     * プロフィール情報を更新するメソッド
     * URL: /mypage/profile
     * メソッド: POST (認証必須)
     */
    public function update(ProfileRequest $request)
    {
        Log::debug('[UserController@update] メソッド開始');

        $user = User::find(auth()->id());
        Log::debug('[UserController@update] ユーザー情報取得', ['user_id' => $user->id, 'user_name' => $user->name, 'email' => $user->email]);

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            Log::debug('[UserController@update] プロフィール画像アップロード', ['path' => $path]);
        } else {
            $path = $user->profile_image;
            Log::debug('[UserController@update] プロフィール画像アップロードなし、既存画像パスを使用', ['path' => $path]);
        }

        $updateData = [
            'name' => $request->input('name'),
            'postal_code' => $request->input('postal_code'),
            'address' => $request->input('address'),
            'building' => $request->input('building'),
            'profile_image' => $path,
        ];

        Log::debug('[UserController@update] 更新データ準備完了', $updateData);

        try {
            $user->update($updateData);
            Log::debug('[UserController@update] ユーザー情報更新成功', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::debug('[UserController@update] ユーザー情報更新エラー', [
                'error_message' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return redirect()->route('user.show')->with('error', 'プロフィール更新に失敗しました。');
        }

        return redirect()->route('user.show')->with('success', 'プロフィールを更新しました。');
    }

    /**
     * ユーザーが購入した商品の一覧を表示するメソッド
     * URL: /mypage?tab=buy
     * メソッド: GET (認証必須)
     */
    public function buyList()
    {
        Log::debug('[UserController@buyList] メソッド開始');

        $user = auth()->user();
        Log::debug('[UserController@buyList] ログインユーザー取得', ['user_id' => $user->id, 'user_name' => $user->name]);

        try {
            $purchasedItems = $user->purchases()
                ->with('item')
                ->get()
                ->map(function ($purchase) {
                    Log::debug('[UserController@buyList][map] purchaseからitemを取得', ['purchase_id' => $purchase->id, 'item_id' => optional($purchase->item)->id]);
                    return $purchase->item;
                });

            Log::debug('[UserController@buyList] 購入履歴取得成功', ['count' => $purchasedItems->count(), 'item_ids' => $purchasedItems->pluck('id')->toArray()]);
        } catch (\Exception $e) {
            Log::debug('[UserController@buyList] 購入履歴取得エラー', ['error_message' => $e->getMessage(), 'user_id' => $user->id]);
            return redirect()->route('user.show')->with('error', '購入履歴の取得中にエラーが発生しました。');
        }

        return view('user.show', [
            'items' => $purchasedItems,
            'tab' => 'buy'
        ]);
    }

    /**
     * ユーザーが出品した商品の一覧を表示するメソッド
     * URL: /mypage?tab=sell
     * メソッド: GET (認証必須)
     */
    public function sellList()
    {
        Log::debug('[UserController@sellList] メソッド開始');

        $user = auth()->user();
        Log::debug('[UserController@sellList] ログインユーザー取得', ['user_id' => $user->id, 'user_name' => $user->name]);

        $items = Item::where('user_id', $user->id)->get();
        Log::debug('[UserController@sellList] 出品商品取得完了', ['count' => $items->count(), 'item_ids' => $items->pluck('id')->toArray()]);

        return view('user.show', ['items' => $items, 'tab' => 'sell']);
    }
}
