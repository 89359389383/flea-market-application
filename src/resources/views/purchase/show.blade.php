@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品購入ページ')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/show.css') }}" />
@endsection

@section('content')
<div class="container">
    <!-- メインコンテンツ（左側） -->
    <div class="main-content">
        <div class="product-header">
            <div class="product-image">
                <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}" class="product-image">
            </div>
            <div class="product-info">
                <h1 class="product-title">{{ $item->name }}</h1>
                <h2 class="product-price">¥{{ number_format($item->price) }}</h2>
            </div>
        </div>

        <!-- 支払い方法 -->
        <div class="section-payment">
            <h2 class="section-title">支払い方法</h2>
            <form method="GET" action="{{ route('purchase.show', ['item_id' => $item->id]) }}">
                <select name="payment_method" id="payment-method" onchange="this.form.submit()">
                    <option value="" {{ request('payment_method') === null ? 'selected' : '' }}>選択してください</option>
                    <option value="コンビニ払い" class="payment-option" {{ request('payment_method') === 'コンビニ払い' ? 'selected' : '' }}>
                        コンビニ払い
                    </option>
                    <option value="カード払い" class="payment-option" {{ request('payment_method') === 'カード払い' ? 'selected' : '' }}>
                        カード払い
                    </option>
                </select>
            </form>
            @error('payment_method')
            <p class="error-message" style="color: red;">{{ $message }}</p>
            @enderror
        </div>

        <!-- 配送先 -->
        <div class="section-address">
            <div class="section-header">
                <h2 class="section-title">配送先</h2>
                <a href="{{ route('address.edit', ['item_id' => $item->id]) }}" class="change-address-link">変更する</a>
            </div>
            <div class="address">
                <input type="hidden" name="postal_code" value="{{ $user->postal_code }}">
                <input type="hidden" name="address" value="{{ $user->address }}">
                <input type="hidden" name="building" value="{{ $user->building }}">
                〒 {{ $user->postal_code }}<br>
                {{ $user->address }}<br>
                {{ $user->building }}
            </div>
        </div>
    </div>

    <!-- サイドバー（右側） -->
    <div class="sidebar">
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">商品代金</div>
                <div class="summary-value">¥{{ number_format($item->price) }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">支払い方法</div>
                <div class="summary-value" id="selected-payment-method">
                    {{ request('payment_method') ?? '選択してください' }}
                </div>
            </div>
        </div>

        <!-- ボタン表示 -->
        @if(request('payment_method') === 'カード払い')
        <form action="{{ route('stripe.checkout', ['item_id' => $item->id]) }}">
            <button type="submit" class="purchase-button">購入する</button>
        </form>
        @elseif(request('payment_method') === 'コンビニ払い')
        <form action="{{ route('purchase.store', ['item_id' => $item->id]) }}" method="POST">
            @csrf
            <input type="hidden" name="payment_method" value="コンビニ払い">
            <input type="hidden" name="postal_code" value="{{ $user->postal_code }}">
            <input type="hidden" name="address" value="{{ $user->address }}">
            <input type="hidden" name="building" value="{{ $user->building }}">
            <button type="submit" class="purchase-button">購入する</button>
        </form>
        @endif
    </div>
</div>
@endsection