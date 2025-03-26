<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use App\Http\Requests\ExhibitionRequest;
use App\Http\Requests\CommentRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Like;

class ItemController extends Controller
{
    /**
     * 商品一覧ページを表示するメソッド
     * URL: /
     * メソッド: GET
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommend');
        $user = Auth::user();
        $items = collect(); // 初期化

        if ($tab === 'mylist') {
            // 認証チェック
            if (!$user) {
                return redirect()->route('login')->with('error', 'マイリストを見るにはログインが必要です。');
            }

            $likedItemIds = $user->likes()->pluck('item_id');
            $query = Item::whereIn('id', $likedItemIds)->with('user');

            $searchQuery = $request->query('name', '');
            if (!empty($searchQuery)) {
                $query->where('name', 'like', "%{$searchQuery}%");
            }

            $items = $query->get();
        } else {
            $query = Item::with('user');
            if ($user) {
                $query->where('user_id', '!=', $user->id);
            }
            $items = $query->get();
        }

        return view('items.index', [
            'items' => $items,
            'tab' => $tab,
            'searchQuery' => $request->query('name', ''),
        ]);
    }

    public function search(Request $request)
    {
        // ユーザーが入力した商品名を取得
        $name = $request->input('name');

        // タブのパラメータを取得（デフォルトは "recommend"）
        $tab = $request->query('tab', 'recommend');

        // 商品データを扱うためのクエリを準備
        $query = Item::with('user');

        if (!empty($name)) {
            $query->where('name', 'like', "%$name%"); // 部分一致検索
        }

        $items = $query->get();

        return view('items.index', ['items' => $items, 'name' => $name, 'tab' => $tab]);
    }

    /**
     * ユーザーが「いいね」した商品一覧ページを表示するメソッド
     * URL: /?tab=mylist
     * メソッド: GET (認証必須)
     */
    public function mylist(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'マイリストを見るにはログインが必要です。');
        }

        // ⭐️【修正】検索キーワードを取得し、マイリストの検索に適用
        $searchQuery = $request->query('name', '');

        // いいねした商品のみ取得
        $likedItemIds = $user->likes()->pluck('item_id');

        $query = Item::whereIn('id', $likedItemIds)->with('user');

        // ⭐️【追加】検索キーワードがある場合に検索条件を適用
        if (!empty($searchQuery)) {
            $query->where('name', 'like', "%{$searchQuery}%");
        }

        $items = $query->get();

        return view('items.index', [
            'items' => $items,
            'tab' => 'mylist',
            'searchQuery' => $searchQuery, // ⭐️【追加】検索ワードをビューに渡す
        ]);
    }

    /**
     * 商品詳細ページを表示するメソッド
     * URL: /item/{item_id}
     * メソッド: GET
     */
    public function show($id)
    {
        // 商品データを取得（ユーザー、カテゴリ、いいね、コメント含む）
        $item = Item::with(['user', 'categories', 'likes', 'comments.user'])->findOrFail($id);

        // カテゴリー情報をログに記録
        Log::info('商品詳細ページ表示', [
            'item_id' => $item->id,
            'categories' => $item->categories // カテゴリー情報をログに追加
        ]);

        return view('items.show', compact('item'));
    }

    /**
     * 商品出品ページを表示するメソッド
     * URL: /sell
     * メソッド: GET (認証必須)
     */
    public function create()
    {
        // 全てのカテゴリを取得して出品フォームに表示します
        $categories = Category::all();

        // items/create.blade.php ビューにカテゴリデータを渡して表示します
        return view('items.create', ['categories' => $categories]);
    }

    /**
     * 新しい商品を保存するメソッド
     * URL: /sell
     * メソッド: POST (認証必須)
     */

    public function store(ExhibitionRequest $request)
    {
        // リクエストデータをログに記録
        Log::info('商品出品リクエストを受信', [
            'user_id' => auth()->id(),
            'request_data' => $request->all()
        ]);

        // 画像のアップロード処理
        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $imagePath = $request->file('image')->store('items', 'public');
                Log::info('画像アップロード成功', ['image_path' => $imagePath]);
            } catch (\Exception $e) {
                Log::error('画像アップロードエラー', ['error' => $e->getMessage()]);
            }
        } else {
            Log::warning('画像未選択のため、nullで登録');
        }

        // 商品データをデータベースに保存
        try {
            $item = Item::create([
                'user_id' => auth()->id(),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'brand_name' => $request->input('brand_name'),
                'condition' => $request->input('condition'),
                'price' => $request->input('price'),
                'sold' => false,
                'image' => 'items/' . basename($imagePath),
            ]);

            Log::info('商品データ保存成功', ['item_id' => $item->id]);

            // 🔽 カテゴリーを保存する処理（修正後）
            $categories = $request->input('categories', []); // 選択したカテゴリを取得
            Log::info('選択したカテゴリ（取得直後）', ['categories' => $categories]);

            if (!empty($categories)) {
                // `$categories` が文字列の場合は explode() で配列に変換
                if (is_string($categories)) {
                    $categories = explode(',', $categories);
                }

                // `$categories` が配列の中にカンマ区切りの文字列を持っている場合（["2,3,4"] みたいな形）
                if (count($categories) === 1 && is_string($categories[0]) && str_contains($categories[0], ',')) {
                    $categories = explode(',', $categories[0]);
                }

                // 各カテゴリIDを整数型に変換
                $categories = array_map('intval', $categories);
                Log::info('整数に変換したカテゴリ', ['categories' => $categories]);

                // カテゴリを保存
                $item->categories()->attach($categories);
                Log::info('カテゴリを保存しました', ['item_id' => $item->id, 'categories' => $categories]);
            } else {
                Log::warning('カテゴリが選択されていません');
            }

            return redirect('/')->with('success', '商品を出品しました。');
        } catch (\Exception $e) {
            Log::error('商品データ保存エラー', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', '商品登録中にエラーが発生しました。');
        }
    }

    /**
     * 商品にコメントを投稿するメソッド
     * URL: /item/{item_id}/comment
     * メソッド: POST (認証必須)
     */
    public function storeComment(CommentRequest $request, $item_id)
    {
        // 商品が存在するかを確認
        $item = Item::findOrFail($item_id);

        // コメントを保存
        Comment::create([
            'user_id' => auth()->id(), // 現在ログイン中のユーザーID
            'item_id' => $item->id, // コメント対象の商品ID
            'comment' => $request->input('comment'), // フォームから送信されたコメント内容
        ]);

        // 商品詳細ページにリダイレクトし、成功メッセージを表示する
        return redirect()->route('items.show', $item_id)->with('success', 'コメントを投稿しました。');
    }

    public function toggleLike($id)
    {
        $item = Item::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'いいねをするにはログインが必要です。');
        }

        // 既にいいねしている場合は削除（解除）
        if ($item->likes()->where('user_id', $user->id)->exists()) {
            $item->likes()->where('user_id', $user->id)->delete();
            return redirect()->back()->with('success', 'いいねを解除しました。');
        }

        // いいねを追加
        Like::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        return redirect()->back()->with('success', 'いいねしました！');
    }
}
