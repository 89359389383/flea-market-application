@extends('layouts.app')

@section('title', 'COACHTECHフリマ - ユーザープロフィール')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/show.css') }}">
@endsection

@section('content')
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-info">
            <div class="avatar"></div>
            <h1 class="username">{{ Auth::user()->name }}</h1>
        </div>
        <a href="{{ route('profile.edit') }}" class="edit-button">プロフィールを編集</a>
    </div>

    <nav class="tabs">
        <a href="#" class="tab active">出品した商品</a>
        <a href="#" class="tab">購入した商品</a>
    </nav>

    <div class="products-grid">
        @foreach ($products as $product)
        <a href="{{ route('products.show', $product->id) }}" class="product-card">
            <div class="product-image">
                <img src="{{ asset('storage/products/' . $product->image) }}" alt="{{ $product->name }}">
            </div>
            <div class="product-name">{{ $product->name }}</div>
        </a>
        @endforeach
    </div>
</div>
@endsection