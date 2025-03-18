@extends('layouts.app')

@section('content')
<div class="container">
    <h2>メール認証が必要です</h2>
    <p>登録したメールアドレスを確認し、認証リンクをクリックしてください。</p>

    @if (session('message'))
    <p class="alert alert-success">{{ session('message') }}</p>
    @endif

    @if (session('resent'))
    <div class="alert alert-success" role="alert">
        {{ __('新しい認証リンクをメールで送信しました。') }}
    </div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <button type="submit" class="btn btn-primary">
            {{ __('認証リンクを再送信') }}
        </button>
    </form>

    <div class="mt-3">
        <a href="http://localhost:8025" class="btn btn-secondary">認証はこちらから</a>
    </div>
</div>
@endsection