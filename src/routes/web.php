<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\UserController;

// Pro追加分コントローラー
use App\Http\Controllers\TradeController;           // 取引チャット用
use App\Http\Controllers\TradeMessageController;    // チャット投稿・編集・削除
use App\Http\Controllers\EvaluationController;      // 取引評価用

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
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
    // 出品画面
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');

    // いいね機能
    Route::post('/items/{id}/toggle-like', [ItemController::class, 'toggleLike'])
        ->name('items.toggleLike');

    // コメント投稿
    Route::post('/item/{item_id}/comment', [ItemController::class, 'storeComment'])
        ->name('items.comment.store');

    // 購入、住所変更
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/stripe/checkout/{item_id}', [PurchaseController::class, 'checkout'])->name('stripe.checkout');
    Route::get('/purchase/address/{item_id}', [AddressController::class, 'edit'])->name('address.edit');
    Route::post('/purchase/address/{item_id}', [AddressController::class, 'update'])->name('address.update');

    // =======================
    // マイページ系（パラメータで切り替え）
    // =======================

    // プロフィール・購入一覧・出品一覧・取引中一覧
    // /mypage?page=sell      ... 出品一覧（デフォルト）
    // /mypage?page=buy       ... 購入一覧
    // /mypage?page=trading   ... 取引中商品一覧（Pro追加）
    Route::get('/mypage', [UserController::class, 'show'])->name('user.show');

    // プロフィール編集
    Route::get('/mypage/profile', [UserController::class, 'edit'])->name('user.edit');
    Route::post('/mypage/profile', [UserController::class, 'update'])->name('user.update');

    // =======================
    // Pro追加: 取引チャット・評価関連
    // =======================

    // チャット画面（個別取引ID） 例: /trade-chat/1
    Route::get('/trade-chat/{trade_id}', [TradeController::class, 'show'])->name('trade.chat.show');

    // チャット投稿
    Route::post('/trade-chat/{trade_id}/message', [TradeMessageController::class, 'store'])->name('trade.message.store');
    // チャット編集
    Route::put('/trade-message/{message_id}', [TradeMessageController::class, 'update'])->name('trade.message.update');
    // チャット削除
    Route::delete('/trade-message/{message_id}', [TradeMessageController::class, 'destroy'])->name('trade.message.destroy');
    // チャット新着既読
    Route::post('/trade-chat/{trade_id}/read', [TradeMessageController::class, 'markAsRead'])->name('trade.message.read');

    // 取引評価モーダル表示
    Route::get('/trade/{trade_id}/evaluate', [EvaluationController::class, 'create'])->name('trade.evaluate.create');
    // 取引評価送信
    Route::post('/trade/{trade_id}/evaluate', [EvaluationController::class, 'store'])->name('trade.evaluate.store');
});

// ===================
// メール認証関連
// ===================
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $user = $request->user();
    $request->fulfill();
    Auth::login($user);
    return redirect('/mypage/profile');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('resent', true);
})->middleware(['auth'])->name('verification.resend');

// Stripe決済のリダイレクト後処理
Route::get('/purchase/complete/{item_id}', [PurchaseController::class, 'complete'])
    ->middleware('auth')->name('purchase.complete');
