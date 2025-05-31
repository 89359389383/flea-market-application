<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// ===================
// 認証不要ページ
// ===================

// 商品一覧
Route::get('/', [ItemController::class, 'index'])->name('items.index');

// 商品検索
Route::get('/search', [ItemController::class, 'search'])->name('items.search');

// 商品詳細
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('items.show');


// ===================
// 認証ページ (Fortify使用)
// ===================

// 会員登録
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.show');
Route::post('/register', [AuthController::class, 'store'])->name('register.store');

// ログイン
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

// ログアウト
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ===================
// 認証必須ページ
// ===================

Route::middleware('auth')->group(function () {
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create'); // 出品画面表示
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store'); // 出品処理

    // いいね機能
    Route::post('/items/{id}/toggle-like', [ItemController::class, 'toggleLike'])
        ->name('items.toggleLike');

    // コメント投稿
    Route::post('/item/{item_id}/comment', [ItemController::class, 'storeComment'])
        ->name('items.comment.store');

    // 購入、住所変更機能
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])->name('purchase.show'); // 購入画面表示
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store'); // 購入処理
    Route::get('/stripe/checkout/{item_id}', [PurchaseController::class, 'checkout'])->name('stripe.checkout'); // stripe決済画面
    Route::get('/purchase/address/{item_id}', [AddressController::class, 'edit'])->name('address.edit'); // 住所変更画面表示
    Route::post('/purchase/address/{item_id}', [AddressController::class, 'update'])->name('address.update'); // 住所変更処理

    // ユーザー機能
    Route::get('/mypage', [UserController::class, 'show'])->name('user.show');

    // プロフィール編集関連
    Route::get('/mypage/profile', [UserController::class, 'edit'])->name('user.edit'); // プロフィール編集画面
    Route::post('/mypage/profile', [UserController::class, 'update'])->name('user.update'); // プロフィール更新処理
});

// メール認証関連のルート
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// 「/email/verify/ユーザーID/ハッシュ値」にアクセスされたときの処理
// このURLは、ユーザーがメールで受け取った認証リンクから来るものです
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {

    // メール認証リンクをクリックしたユーザーの情報を取得
    $user = $request->user(); // 今ログインしようとしているユーザーの情報を取得する

    // メールアドレスが本物であることを確認して「認証完了」にする
    $request->fulfill(); // fulfill()は「メール認証済み」にする特別な関数

    // ユーザーを自動的にログイン状態にする（もう一度ログインしなくていい）
    Auth::login($user); // 認証されたユーザーを自動的にログイン状態にする

    // ログインしたら「プロフィール設定」ページへ案内（リダイレクト）
    return redirect('/mypage/profile'); // ユーザーが情報を入力するページへ移動

    // ここでは、「auth」と「signed」という2つの特別なルール（ミドルウェア）を使って安全性を保っています
    // auth → ユーザーが一度ログインしていることを確認（未認証の人はアクセスできない）
    // signed → メールのリンクが書き換えられていないことを確認（セキュリティ対策）
})->middleware(['auth', 'signed'])->name('verification.verify');

// 「メール認証の再送信」のためのルート（URLの入り口）を定義しています
Route::post('/email/verification-notification', function (Request $request) {

    // 現在ログインしているユーザーに対して
    // メール認証リンクをもう一度送信します
    $request->user()->sendEmailVerificationNotification();

    // 前のページに戻って、「認証メールを再送信しました」と知らせるメッセージを持たせて戻ります
    return back()->with('resent', true);

    // このルートを使うには、「ログインしている」ことが必要です（authミドルウェア）
})->middleware(['auth'])->name('verification.resend');

// Stripe決済のリダイレクト後処理
Route::get('/purchase/complete/{item_id}', [PurchaseController::class, 'complete'])->middleware('auth')->name('purchase.complete');
