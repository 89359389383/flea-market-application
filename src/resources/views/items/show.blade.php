@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/show.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="product-image">
        <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}">
        @if ($item->sold)
        <div class="sold-label">Sold</div>
        @endif
    </div>
    <div class="product-details">
        <h1 class="product-title">{{ $item->name }}</h1>
        <p class="brand-name">{{ $item->brand_name }}</p>
        <p class="price">¥{{ number_format($item->price) }} (税込)</p>

        <div class="rating-section">
            <div class="like">
                <!-- いいねボタン -->
                <form action="{{ route('items.toggleLike', $item->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="like-button {{ auth()->user() && $item->likes->contains('user_id', auth()->id()) ? 'liked' : '' }}">
                        ☆
                    </button>
                </form>
                <div class="like-count">{{ $item->likes->count() }}</div>
            </div>
            <div>
                <div class="comment-icon">💬</div>
                <div class="comment-count">{{ $item->comments_count }}</div>
            </div>
        </div>
        　　　　
        <form action="{{ route('purchase.show', ['item_id' => $item->id]) }}" method="GET">
            @if ($item->sold)
            <!-- 売り切れの場合 -->
            <button type="button" class="sold-out-button" disabled>売り切れました</button>
            @else
            <!-- 売り切れていない場合 -->
            <button type="submit" class="purchase-button">購入手続きへ</button>
            @endif
        </form>

        <h2 class="section-title">商品説明</h2>
        <div class="product-description">
            {{ $item->description }}
        </div>

        <h2 class="section-title">商品の情報</h2>
        <div class="product-info">
            <div class="info-row">
                <div class="info-label">カテゴリー</div>
                <div class="info-category-value">
                    @foreach ($item->categories as $category)
                    <span class="tag">{{ $category->name }}</span>
                    @endforeach
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">商品の状態</div>
                <div class="info-condition-value">{{ $item->condition }}</div>
            </div>
        </div>

        <div class="comment-section">
            <div class="comment-header">コメント ({{ $item->comments->count() }})</div>
            <div class="comment-list">
                @foreach ($item->comments as $comment)
                <div class="comment">
                    <div class="comment-content" style="display: flex; align-items: center;">
                        <div class="comment-avatar">
                            <img src="{{ asset('storage/' . $comment->user->profile_image) }}" class="comment-avatar-img">
                        </div>
                        <div class="comment-username">{{ $comment->user->name }}</div>
                    </div>
                    <div class="comment-text">{{ $comment->comment }}</div>
                </div>
                @endforeach
            </div>
        </div>

        @auth
        <h3 class="section-title">商品へのコメント</h3>
        <form action="{{ route('items.comment.store', $item->id) }}" method="POST" class="items.comment">
            @csrf
            <textarea class="comment-input" name="comment" placeholder="コメントを入力してください"></textarea>
            @error('comment')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
            <button type="submit" class="comment-submit-button">コメントを送信する</button>
        </form>
        @else
        <form action="{{ route('login') }}" method="GET">
            <button type="submit" class="login-button">ログインしてコメントする</button>
        </form>
        @endauth
    </div>
</div>
@endsection