<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class Handler extends ExceptionHandler
{
    /**
     * 例外のうち、報告しないもののリスト
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * バリデーション例外時にフラッシュしない入力項目のリスト
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 例外処理の登録
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * 例外のレンダリング処理
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $exception)
    {
        // ログインユーザー情報の取得
        $user = Auth::check() ? [
            'id' => Auth::user()->id,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
        ] : 'Guest';

        // Fortify の設定情報
        $fortifyConfig = [
            'guard' => Config::get('fortify.guard'),
            'passwords' => Config::get('fortify.passwords'),
            'home' => Config::get('fortify.home'),
            'features' => Config::get('fortify.features'),
        ];

        // .env の環境変数情報
        $envConfig = [
            'SESSION_DRIVER' => env('SESSION_DRIVER'),
            'CACHE_DRIVER' => env('CACHE_DRIVER'),
            'FORTIFY_GUARD' => env('FORTIFY_GUARD'),
            'APP_ENV' => env('APP_ENV'),
        ];

        // ルート情報の取得（/login や /register が正しく設定されているか確認）
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
                'middleware' => $route->middleware(),
            ];
        })->toArray();

        // SQLクエリログの記録（直前のクエリを記録）
        DB::listen(function ($query) {
            Log::info('SQL Query Log', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });

        // 例外の詳細をログに記録
        Log::error('【例外キャッチ】', [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => $request->headers->all(), // HTTPヘッダー
            'params' => $request->all(), // リクエストパラメータ
            'session' => Session::all(), // セッション情報
            'user' => $user, // 認証ユーザー情報
            'fortify_config' => $fortifyConfig, // Fortify の設定情報
            'env_config' => $envConfig, // .env の環境変数情報
            'routes' => $routes, // ルート情報
            'trace' => $exception->getTraceAsString(), // スタックトレース
        ]);

        return parent::render($request, $exception);
    }
}
