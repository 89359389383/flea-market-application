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
                <img src="{{ Auth::user()->profile_image ? Storage::url(Auth::user()->profile_image) : '' }}" alt="プロフィール画像">
            </div>
            <!-- ユーザー名の表示 -->
            <h1 class="username">{{ Auth::user()->name }}</h1>
        </div>
        <a href="{{ route('user.edit') }}" class="edit-button">プロフィールを編集</a>
    </div>

    @php
    // ルートから `tab` パラメータが渡っている場合、それを優先
    $tab = request()->query('tab', $tab ?? 'sell');
    @endphp

    <nav class="tabs">
        <a href="{{ route('user.sellList') }}" class="tab {{ $tab == 'sell' ? 'active' : '' }}">出品した商品</a>
        <a href="{{ url('/mypage?tab=buy') }}" class="tab {{ $tab == 'buy' ? 'active' : '' }}">購入した商品</a>
    </nav>

    <div class="product-grid">
        @if ($tab == 'buy')
        @foreach ($items as $item)
        <div class="product-item">
            <a href="{{ route('items.show', $item->id) }}">
                <div class="image-container">
                    <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}" class="product-image">
                    <div class="sold-label">Sold</div> <!-- 購入済みなので "Sold" を強制表示 -->
                </div>
                <div class="product-info">
                    <span class="product-name">{{ $item->name }}</span>
                </div>
            </a>
        </div>
        @endforeach
        @else
        @foreach ($items as $item)
        <div class="product-item">
            <a href="{{ route('items.show', $item->id) }}">
                <div class="image-container">
                    <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}" class="product-image">
                    @if ($item->sold)
                    <div class="sold-label">Sold</div>
                    @endif
                </div>
                <div class="product-info">
                    <span class="product-name">{{ $item->name }}</span>
                </div>
            </a>
        </div>
        @endforeach
        @endif
    </div>
</div>
@endsection