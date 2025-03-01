@extends('layouts.app')

@section('title', 'COACHTECHフリマ - ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}" />
@endsection

@section('content')
<div class="container">
    <h1>ログイン</h1>
    <form action="{{ route('login.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}">
            @error('email')
            <p class="error-message" style="color: red;">
                {!! nl2br(e($message)) !!}
            </p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password">
            @error('password')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <button type="submit" class="submit-button">ログインする</button>

        <a href="{{ route('register.show') }}" class="registration-link">会員登録はこちら</a>
    </form>
</div>
@endsection