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
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="header-link">ログアウト</button>
            </form>
            <a href="{{ route('user.show') }}" class="header-link">マイページ</a>
            <a href="{{ route('items.create') }}" class="header-button">出品</a>
        </div>
        @endif
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>