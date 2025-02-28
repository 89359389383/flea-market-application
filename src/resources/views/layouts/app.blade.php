<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'COACHTECHフリマ')</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>

<body>
    <!-- ヘッダー -->
    <header class="header">
        <div class="header-left">
            <div class="logo">
                <span>COACHTECH</span>
            </div>
        </div>

        <!-- ログイン・会員登録ページでは非表示 -->
        @if (!Request::is('login') && !Request::is('register'))
        <div class="header-center">
            <input type="text" class="search-input" placeholder="なにをお探しですか？" />
        </div>
        <div class="header-right">
            <a href="#" class="header-link">ログアウト</a>
            <a href="#" class="header-link">マイページ</a>
            <button class="header-button">出品</button>
        </div>
        @endif
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>