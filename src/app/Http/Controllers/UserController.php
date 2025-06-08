<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $user = auth()->user();

        $tab = $request->query('page', 'sell');

        // 評価平均と評価数
        $average_score = $user->evaluationsReceived()->avg('score');
        $evaluations_count = $user->evaluationsReceived()->count();

        // 取引中商品のトレード一覧を取得（未読メッセージ数計算用）
        $trades_for_unread = Trade::with('messages')
            ->where('is_completed', false)
            ->where(function ($query) use ($user) {
                $query->where('seller_id', $user->id)
                    ->orWhere('buyer_id', $user->id);
            })
            ->get();

        // 未読メッセージ総数計算
        $unread_messages_total = 0;
        foreach ($trades_for_unread as $trade) {
            $unread_count = $trade->messages()
                ->where('user_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
            $unread_messages_total += $unread_count;
        }

        if ($tab === 'trading') {
            $trades = Trade::with(['item', 'seller', 'buyer', 'messages'])
                ->where('is_completed', false)
                ->where(function ($query) use ($user) {
                    $query->where('seller_id', $user->id)
                        ->orWhere('buyer_id', $user->id);
                })
                ->withMax('messages', 'created_at')
                ->orderByDesc(DB::raw('COALESCE(messages_max_created_at, trades.updated_at)'))
                ->get();

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
            $trades = Trade::with(['item'])
                ->where('is_completed', true)
                ->where('buyer_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->get();

            $items = $trades->map(function ($trade) {
                return $trade->item;
            });

            return view('user.show', [
                'user' => $user,
                'tab' => $tab,
                'items' => $items,
                'average_score' => $average_score,
                'evaluations_count' => $evaluations_count,
                'unread_messages_total' => $unread_messages_total,
            ]);
        }

        $items = Item::where('user_id', $user->id)->get();

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
        $user = auth()->user();

        return view('user.edit', ['user' => $user]);
    }

    /**
     * プロフィール情報を更新するメソッド
     * URL: /mypage/profile
     * メソッド: POST (認証必須)
     */
    public function update(ProfileRequest $request)
    {
        $user = User::find(auth()->id());

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
        } else {
            $path = $user->profile_image;
        }

        $updateData = [
            'name' => $request->input('name'),
            'postal_code' => $request->input('postal_code'),
            'address' => $request->input('address'),
            'building' => $request->input('building'),
            'profile_image' => $path,
        ];

        try {
            $user->update($updateData);
        } catch (\Exception $e) {
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
        $user = auth()->user();

        try {
            $purchasedItems = $user->purchases()
                ->with('item')
                ->get()
                ->map(function ($purchase) {
                    return $purchase->item;
                });
        } catch (\Exception $e) {
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
        $user = auth()->user();

        $items = Item::where('user_id', $user->id)->get();

        return view('user.show', ['items' => $items, 'tab' => 'sell']);
    }
}
