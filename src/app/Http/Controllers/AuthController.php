<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * 会員登録フォームを表示するメソッド
     * URL: /register
     * メソッド: GET
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * 新しいユーザーを登録するメソッド
     * URL: /register
     * メソッド: POST
     */
    public function store(RegisterRequest $request)
    {
        // ユーザーを作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // ユーザーを自動ログイン
        Auth::login($user);

        // 認証メールを送信
        $user->sendEmailVerificationNotification();

        // メール認証ページにリダイレクト
        return redirect()->route('verification.notice');
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
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // 認証を試みます (認証に成功すれば true を返します)
        if (Auth::attempt($credentials)) {
            // 認証に成功した場合、ユーザーをトップページにリダイレクトします
            return redirect()->intended('/');
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
        Auth::logout();

        return redirect('/')->with('success', 'ログアウトしました。');
    }
}
