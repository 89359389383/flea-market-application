<?php

// ファイルの場所を指定するための名前空間を宣言します
namespace App\Http\Controllers;

// RequestクラスとUserモデルを使用できるようにします
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;  // 認証処理を行うために使用します
use Illuminate\Support\Facades\Hash;  // パスワードのハッシュ化に使用します

class AuthController extends Controller
{
    /**
     * 会員登録フォームを表示するメソッド
     * URL: /register
     * メソッド: GET
     */
    public function showForm()
    {
        // ビュー(auth/register.blade.php)を表示します
        return view('auth.register');
    }

    /**
     * 新しいユーザーを登録するメソッド
     * URL: /register
     * メソッド: POST
     */
    public function store(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ユーザーを自動的にログインさせます
        Auth::login($user);

        // ユーザーをプロフィール設定ページにリダイレクトします
        return redirect('/mypage/profile')->with('success', '会員登録が完了しました。');
    }

    /**
     * ログインフォームを表示するメソッド
     * URL: /login
     * メソッド: GET
     */
    public function showLoginForm()
    {
        // ビュー(auth/login.blade.php)を表示します
        return view('auth.login');
    }

    /**
     * ユーザーをログインさせるメソッド
     * URL: /login
     * メソッド: POST
     */
    public function login(Request $request)
    {
        // フォームから送信されたメールアドレスとパスワードを取得して配列にします
        $credentials = $request->only('email', 'password');

        // 認証を試みます (認証に成功すれば true を返します)
        if (Auth::attempt($credentials)) {
            // 認証に成功した場合、ユーザーをトップページにリダイレクトします
            return redirect('/')->with('success', 'ログインに成功しました。');
        }

        // 認証に失敗した場合、ログインページにリダイレクトし、エラーメッセージを表示します
        return back()->withErrors(['email' => 'ログイン情報が登録されていません']);
    }

    /**
     * ユーザーをログアウトさせるメソッド
     * URL: /logout
     * メソッド: POST
     */
    public function logout()
    {
        // ユーザーをログアウトさせます
        Auth::logout();

        // トップページにリダイレクトし、メッセージを表示します
        return redirect('/')->with('success', 'ログアウトしました。');
    }
}
