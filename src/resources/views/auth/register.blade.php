@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}" />
@endsection

@section('content')
<div class="container">
    <h1>会員登録</h1>
    <form>
        <div class="form-group">
            <label for="username">ユーザー名</label>
            <input type="text" id="username" name="username">
        </div>

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email">
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password">
        </div>

        <div class="form-group">
            <label for="confirm-password">確認用パスワード</label>
            <input type="password" id="confirm-password" name="confirm-password">
        </div>

        <button type="submit" class="submit-button">登録する</button>

        <div class="login-link">
            <a href="#">ログインはこちら</a>
        </div>
    </form>
</div>
@endsection