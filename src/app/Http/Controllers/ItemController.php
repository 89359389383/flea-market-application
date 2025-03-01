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

class ItemController extends Controller
{
    /**
     * 商品一覧ページを表示するメソッド
     * URL: /
     * メソッド: GET
     */
    public function index()
    {
        // 全ての商品のデータをデータベースから取得します
        // 'with'メソッドで関連するユーザー情報も取得します
        $items = Item::with('user')->where('sold', false)->get();

        // items/index.blade.php ビューに商品データを渡して表示します
        return view('items.index', ['items' => $items]);
    }

    public function search(Request $request)
    {
        // ユーザーが入力した商品名を取得
        $name = $request->input('name');

        // 商品データを扱うためのクエリを準備
        $query = Item::with('user')->where('sold', false);

        // 商品名が入力されている場合、名前で部分一致検索を追加
        if (!empty($name)) {
            $query->where('name', 'like', "%$name%"); // 部分一致検索
        }

        // クエリを実行して商品一覧を取得
        $items = $query->get();

        // items/index.blade.php ビューに商品データを渡して表示します
        return view('items.index', ['items' => $items, 'name' => $name]);
    }

    /**
     * ユーザーが「いいね」した商品一覧ページを表示するメソッド
     * URL: /?tab=mylist
     * メソッド: GET (認証必須)
     */
    public function mylist()
    {
        // 現在ログインしているユーザーを取得します
        $user = User::find(auth()->id()); // 現在のユーザーをUserモデルから取得

        // ユーザーが「いいね」した商品を取得します
        // 'with'メソッドで関連するユーザー情報も取得します
        $items = $user->likes()->with('user')->get();

        // items/index.blade.php ビューに商品データを渡して表示します
        return view('items.index', ['items' => $items]);
    }

    /**
     * 商品詳細ページを表示するメソッド
     * URL: /item/{item_id}
     * メソッド: GET
     */
    public function show($id)
    {
        // 指定されたIDの商品のデータを取得します
        // 関連するユーザー情報も一緒に取得します
        $item = Item::with(['user', 'categories'])->findOrFail($id);

        // items/show.blade.php ビューに商品データを渡して表示します
        return view('items.show', ['item' => $item]);
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
            $imagePath = $request->file('image')->store('items', 'public');
            $item = Item::create([
                'user_id' => auth()->id(),
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'brand_name' => $request->input('brand_name'),
                'condition' => $request->input('condition'),
                'price' => $request->input('price'),
                'sold' => false,
                'image' => 'items/' . basename($imagePath), // パスを items/ + ファイル名 に修正
            ]);

            Log::info('商品データ保存成功', ['item_id' => $item->id]);
        } catch (\Exception $e) {
            Log::error('商品データ保存エラー', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', '商品登録中にエラーが発生しました。');
        }

        // カテゴリーをIDの配列として取得
        $categories = $request->input('categories', []);
        if (!empty($categories)) {
            $item->categories()->attach($categories);
        }

        // 商品一覧ページにリダイレクト
        return redirect('/')->with('success', '商品を出品しました。');
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
}
