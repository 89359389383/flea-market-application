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
                商品画像
            </div>
            <div class="product-info">
                <h1>商品名</h1>
                <div class="product-price">¥ 47,000</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">支払い方法</h2>
            <select>
                <option>選択してください</option>
            </select>
        </div>

        <div class="section">
            <h2 class="section-title">配送先</h2>
            <a href="#" class="change-button">変更する</a>
            <div class="address">
                〒 XXX-YYYY<br>
                ここには住所と建物が入ります
            </div>
        </div>
    </div>

    <div class="sidebar">
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">商品代金</div>
                <div class="summary-value">¥ 47,000</div>
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