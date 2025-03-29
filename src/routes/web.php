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
    // 商品出品、マイリスト表示
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create'); // 出品画面表示
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store'); // 出品処理
    // Route::get('/mylist', [ItemController::class, 'mylist'])->name('items.mylist'); // マイリスト(いいねした商品)

    // いいね機能
    Route::post('/items/{id}/toggle-like', [ItemController::class, 'toggleLike'])
        ->name('items.toggleLike');

    // コメント投稿
    Route::post('/item/{item_id}/comment', [ItemController::class, 'storeComment'])
        ->name('items.comment.store');

    // 購入、住所変更機能
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])->name('purchase.show'); // 購入画面表示
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store'); // 購入処理
    Route::get('/stripe/checkout/{item_id}', [PurchaseController::class, 'checkout'])->name('stripe.checkout');
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

// メール認証を完了したら、自動ログインしプロフィール設定へ
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $user = $request->user();
    $request->fulfill(); // 認証を完了

    Auth::login($user); // 認証完了したら自動ログイン

    return redirect('/mypage/profile'); // プロフィール設定画面へリダイレクト
})->middleware(['auth', 'signed'])->name('verification.verify');

// メール認証の再送信
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('resent', true);
})->middleware(['auth'])->name('verification.resend');
