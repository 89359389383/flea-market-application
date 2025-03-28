<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\Comment;
use App\Http\Requests\ExhibitionRequest;
use App\Http\Requests\CommentRequest;
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
        // タブパラメータを取得（デフォルトは'recommend'）
        $tab = $request->query('tab', 'recommend');
        // 現在ログインしているユーザーを取得
        $user = Auth::user();
        // 商品コレクションを初期化
        $items = collect();

        if ($tab === 'mylist') {
            // マイリスト表示の場合
            // 未ログインユーザーはログインページにリダイレクト
            if (!$user) {
                return redirect()->route('login')->with('error', 'マイリストを見るにはログインが必要です。');
            }

            // ユーザーがいいねした商品のIDを取得
            $likedItemIds = $user->likes()->pluck('item_id');
            // いいねした商品のクエリを構築
            $query = Item::whereIn('id', $likedItemIds)->with('user');

            // 検索キーワードがある場合は検索条件を追加
            $searchQuery = $request->query('name', '');
            if (!empty($searchQuery)) {
                $query->where('name', 'like', "%{$searchQuery}%");
            }

            // クエリを実行して商品を取得
            $items = $query->get();
        } else {
            // 通常の商品一覧表示の場合
            $query = Item::with('user');
            // ログインユーザーの場合は自分の商品を除外
            if ($user) {
                $query->where('user_id', '!=', $user->id);
            }
            // クエリを実行して商品を取得
            $items = $query->get();
        }

        // ビューにデータを渡して表示
        return view('items.index', [
            'items' => $items,
            'tab' => $tab,
            'searchQuery' => $request->query('name', ''),
        ]);
    }

    /**
     * 商品を検索するメソッド
     * URL: /search
     * メソッド: GET
     */
    public function search(Request $request)
    {
        // 検索キーワードを取得
        $name = $request->input('name');
        // タブパラメータを取得（デフォルトは'recommend'）
        $tab = $request->query('tab', 'recommend');

        // 商品クエリを構築
        $query = Item::with('user');

        // 検索キーワードがある場合は検索条件を追加
        if (!empty($name)) {
            $query->where('name', 'like', "%$name%");
        }

        // クエリを実行して商品を取得
        $items = $query->get();

        // ビューにデータを渡して表示
        return view('items.index', ['items' => $items, 'name' => $name, 'tab' => $tab]);
    }

    /**
     * ユーザーが「いいね」した商品一覧ページを表示するメソッド
     * URL: /?tab=mylist
     * メソッド: GET (認証必須)
     */
    public function mylist(Request $request)
    {
        // 現在ログインしているユーザーを取得
        $user = Auth::user();

        // 未ログインユーザーはログインページにリダイレクト
        if (!$user) {
            return redirect()->route('login')->with('error', 'マイリストを見るにはログインが必要です。');
        }

        // 検索キーワードを取得
        $searchQuery = $request->query('name', '');

        // ユーザーがいいねした商品のIDを取得
        $likedItemIds = $user->likes()->pluck('item_id');

        // いいねした商品のクエリを構築
        $query = Item::whereIn('id', $likedItemIds)->with('user');

        // 検索キーワードがある場合は検索条件を追加
        if (!empty($searchQuery)) {
            $query->where('name', 'like', "%{$searchQuery}%");
        }

        // クエリを実行して商品を取得
        $items = $query->get();

        // ビューにデータを渡して表示
        return view('items.index', [
            'items' => $items,
            'tab' => 'mylist',
            'searchQuery' => $searchQuery,
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
        // 画像のアップロード処理
        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $imagePath = $request->file('image')->store('items', 'public');
            } catch (\Exception $e) {
                // エラー処理
            }
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

            // カテゴリーを保存する処理
            $categories = $request->input('categories', []);

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

                // カテゴリを保存
                $item->categories()->attach($categories);
            }

            return redirect('/')->with('success', '商品を出品しました。');
        } catch (\Exception $e) {
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
