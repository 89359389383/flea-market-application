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
        // 最初に、あとで商品データを入れるための「空の箱」を用意しておく
        $items = collect();

        if ($tab === 'mylist') {
            // 「マイリスト」タブが選ばれている場合の処理

            // ユーザーがログインしていないなら、ログインページに移動させる
            if (!$user) {
                return redirect()->route('login')->with('error', 'マイリストを見るにはログインが必要です。');
            }

            // ログイン中のユーザーが「いいね」した商品のID（番号）だけを集める
            $likedItemIds = $user->likes()->pluck('item_id');

            // 「いいね」した商品の中から、該当する商品を探す準備をする
            // さらに「商品を出した人の情報」も一緒に取り出せるようにする
            $query = Item::whereIn('id', $likedItemIds)->with('user');

            // 商品名で検索したいキーワードが送られてきているかチェック
            $searchQuery = $request->query('name', '');
            if (!empty($searchQuery)) {
                // 検索キーワードがあれば、商品名にそれが含まれているものだけを選ぶようにする
                $query->where('name', 'like', "%{$searchQuery}%");
            }

            // 上で作った条件を使って、実際に商品データを取り出す
            $items = $query->get();
        } else {
            // 「おすすめ」などの通常タブが選ばれているときの処理

            // 商品一覧を取り出す準備（商品を出した人の情報も一緒に取り出す）
            $query = Item::with('user');

            // もしログインしているなら、「自分が出品した商品」は一覧から外すようにする
            if ($user) {
                $query->where('user_id', '!=', $user->id);
            }

            // 条件に合った商品を取り出す
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
        // ----------------------------
        // 画像のアップロード処理
        // ----------------------------
        $imagePath = null; // 画像ファイルの保存場所を入れる変数。最初は空っぽ。

        // フォームから画像が送られてきているかを確認
        if ($request->hasFile('image')) {
            try {
                // 画像を "storage/app/public/items" フォルダに保存して、そのパスを取得
                // たとえば "items/123456789_photo.jpg" のようになる
                $imagePath = $request->file('image')->store('items', 'public');
            } catch (\Exception $e) {
                // もし保存中に何か問題が起きたら、ここでエラー処理（今は何もしていない）
            }
        }

        // ----------------------------
        // 商品データをデータベースに保存
        // ----------------------------
        try {
            $item = Item::create([
                'user_id' => auth()->id(), // 今ログインしているユーザーのIDを登録
                'name' => $request->input('name'), // フォームから送られてきた商品名
                'description' => $request->input('description'), // 商品の説明
                'brand_name' => $request->input('brand_name'), // ブランド名
                'condition' => $request->input('condition'), // 商品の状態（例：新品、中古など）
                'price' => $request->input('price'), // 商品の価格
                'sold' => false, // 初めて登録するので「売り切れではない」状態で登録
                'image' => 'items/' . basename($imagePath), // 保存された画像のファイル名だけ取り出してパスに追加
            ]);

            // ----------------------------
            // カテゴリーを保存する処理
            // ----------------------------

            // フォームから送られてきたカテゴリーデータを取得（何もなければ空の配列に）
            $categories = $request->input('categories', []);

            if (!empty($categories)) {
                // -------------------------
                // パターン①：文字列で送られてきた場合（例："2,3,4"）
                // explode() でカンマごとに分けて配列に変換（["2", "3", "4"] に）
                // -------------------------
                if (is_string($categories)) {
                    $categories = explode(',', $categories);
                }

                // -------------------------
                // パターン②：配列にはなっているけど、中にカンマ付きの文字が1個だけ入っている場合（例：["2,3,4"]）
                // このときも、カンマで分けて配列に直す（["2", "3", "4"] に）
                // -------------------------
                if (
                    count($categories) === 1 &&                  // 配列の要素が1個だけで、
                    is_string($categories[0]) &&                 // その中身が文字列で、
                    str_contains($categories[0], ',')            // カンマが含まれているとき
                ) {
                    $categories = explode(',', $categories[0]);  // カンマで区切ってバラバラにする
                }

                // -------------------------
                // 最後に、文字（"2"など）を整数（2）に変換する
                // データベースに保存するとき、正しく処理されるようにするため
                // -------------------------
                $categories = array_map('intval', $categories);

                // 商品とカテゴリの関係を保存（中間テーブルに登録）
                $item->categories()->attach($categories);
            }

            // 成功したらトップページに戻って「出品しました」と表示
            return redirect('/')->with('success', '商品を出品しました。');
        } catch (\Exception $e) {
            // もし商品登録中にエラーが起きたら、前のページに戻ってエラーメッセージを表示
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
