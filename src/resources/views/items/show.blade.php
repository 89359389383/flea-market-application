@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/show.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="product-image">商品画像</div>
    <div class="product-details">
        <h1 class="product-title">商品名がここに入る</h1>
        <p class="brand-name">ブランド名</p>
        <p class="price">¥47,000 (税込)</p>

        <div class="rating-section">
            <div>
                <div class="stars">
                    <span class="star">★</span>
                </div>
                <div class="star-count">3</div>
            </div>
            <div>
                <div class="comment-icon">◎</div>
                <div class="comment-count">1</div>
            </div>
        </div>

        <button class="purchase-button">購入手続きへ</button>

        <h2 class="section-title">商品説明</h2>
        <div class="product-description">
            <p>カラー：グレー</p>
            <p>新品</p>
            <p>商品の状態は良好です。傷もありません。</p>
            <p>購入後、即発送いたします。</p>
        </div>

        <h2 class="section-title">商品の情報</h2>
        <div class="product-info">
            <div class="info-row">
                <div class="info-label">カテゴリー</div>
                <div class="info-value">
                    <span class="tag">洋服</span>
                    <span class="tag">メンズ</span>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">商品の状態</div>
                <div class="info-value">良好</div>
            </div>
        </div>

        <h2 class="section-title">コメント</h2>
        <div class="comment-section">
            <h3 class="comment-header">コメント (1)</h3>
            <div class="comment-list">
                <div class="comment">
                    <div class="comment-avatar"></div>
                    <div class="comment-content">
                        <div class="comment-username">admin</div>
                        <div class="comment-text">こちらにコメントが入ります。</div>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="section-title">商品へのコメント</h3>
        <div class="comment-form">
            <textarea class="comment-input" placeholder="コメントを入力してください"></textarea>
            <button class="comment-submit-button">コメントを送信する</button>
        </div>
    </div>
</div>
@endsection