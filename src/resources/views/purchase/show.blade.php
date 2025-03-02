@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品購入ページ')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/show.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="main-content">
        <div class="product-header">
            <div class="product-image">
                <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}" class="product-image">
            </div>
            <div class="product-info">
                <h1 class="product-title">{{ $item->name }}</h1>
                <h1 class="product-price">¥{{ number_format($item->price) }}</h1>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">支払い方法</h2>
            <select name="payment_method">
                <option>選択してください</option>
                <option value="convenience">コンビニ払い</option>
                <option value="card">カード払い</option>
            </select>
        </div>

        <div class="section">
            <h2 class="section-title">配送先</h2>
            <a href="{{ route('address.edit', ['item_id' => $item->id]) }}">変更する</a>
            <div class="address">
                〒 {{ $user->postal_code }}<br>
                {{ $user->address }}<br>
                {{ $user->building }}
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">商品代金</div>
                <div class="summary-value">¥{{ number_format($item->price) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">支払い方法</div>
                <div class="summary-value">コンビニ払い</div>
            </div>
        </div>
        <button class="purchase-button">購入する</button>
    </div>
</div>
@endsection