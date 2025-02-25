<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Category;
use App\Models\User;

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
    public function store(Request $request)
    {
        // 現在ログインしているユーザーのIDを取得して、商品データを保存します
        $item = Item::create([
            'user_id' => auth()->id(),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'brand_name' => $request->input('brand_name'),
            'condition' => $request->input('condition'),
            'price' => $request->input('price'),
            'sold' => false, // 初期状態は未販売（false）
            'image' => $request->hasFile('image') // リクエストに画像ファイルが含まれているかを確認します
                ? $request->file('image')->store('images', 'public') // 画像ファイルを storage/app/public/images フォルダに保存し、その保存先のパスを取得します。'images' は保存先フォルダ名です。'public' は public ディスクを使うことを意味します
                : null, // 画像がなければ null を設定します
        ]);

        // 選択されたカテゴリを関連付けます
        $item->categories()->attach($request->input('categories'));

        // 商品一覧ページにリダイレクトしてメッセージを表示します
        return redirect('/')->with('success', '商品を出品しました。');
    }
}
