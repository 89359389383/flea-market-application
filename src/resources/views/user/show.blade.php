@extends('layouts.app')

@section('title', 'COACHTECHフリマ - ユーザープロフィール')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/show.css') }}">
@endsection

@section('content')
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-info">
            <!-- プロフィール画像の表示 -->
            <div class="avatar">
                <img src="{{ Auth::user()->profile_image ? Storage::url(Auth::user()->profile_image) : asset('images/default-avatar.png') }}" alt="プロフィール画像">
            </div>
            <!-- ユーザー名の表示 -->
            <h1 class="username">{{ Auth::user()->name }}</h1>
        </div>
        <a href="{{ route('user.edit') }}" class="edit-button">プロフィールを編集</a>
    </div>

    <nav class="tabs">
        <a href="{{ route('user.sellList') }}" class="tab {{ request()->is('mypage?tab=sell') ? 'active' : '' }}">出品した商品</a>
        <a href="{{ route('user.buyList') }}" class="tab {{ request()->is('mypage?tab=buy') ? 'active' : '' }}">購入した商品</a>
    </nav>

    <div class="product-grid">
        @foreach ($items as $item)
        <div class="product-item">
            <a href="{{ route('items.show', $item->id) }}">
                <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}"
                    class="product-image">
                <div class="product-info">
                    <span class="product-name">{{ $item->name }}</span>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection