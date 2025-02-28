@extends('layouts.app')

@section('title', 'COACHTECHフリマ - ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}" />
@endsection

@section('content')
<div class="container">
    <h1>ログイン</h1>
    <form>
        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email">
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password">
        </div>

        <button type="submit" class="submit-button">ログインする</button>

        <div class="registration-link">
            <a href="#">会員登録はこちら</a>
        </div>
    </form>
</div>
@endsection