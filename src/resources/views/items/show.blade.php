@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/show.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="product-image">
        <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}">
    </div>
    <div class="product-details">
        <h1 class="product-title">{{ $item->name }}</h1>
        <p class="brand-name">{{ $item->brand_name }}</p>
        <p class="price">¥{{ number_format($item->price) }} (税込)</p>

        <div class="rating-section">
            <div>
                <!-- いいねボタン -->
                <form action="{{ route('items.toggleLike', $item->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="like-button {{ auth()->user() && $item->likes->contains('user_id', auth()->id()) ? 'liked' : '' }}">
                        ☆
                    </button>
                </form>
                <span class="like-count">{{ $item->likes->count() }}</span>
            </div>
            <div>
                <div class="comment-icon">◎</div>
                <div class="comment-count">{{ $item->comments_count }}</div>
            </div>
        </div>
        　　　　
        <form action="{{ route('purchase.show', ['item_id' => $item->id]) }}" method="GET">
            <button type="submit" class="purchase-button">購入手続きへ</button>
        </form>

        <h2 class="section-title">商品説明</h2>
        <div class="product-description">
            <p>{{ $item->description }}</p>
        </div>

        <h2 class="section-title">商品の情報</h2>
        <div class="product-info">
            <div class="info-row">
                <div class="info-label">カテゴリー</div>
                <div class="info-value">
                    @foreach ($item->categories as $category)
                    <span class="tag">{{ $category->name }}</span>
                    @endforeach
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">商品の状態</div>
                <div class="info-value">{{ $item->condition }}</div>
            </div>
        </div>

        <h2 class="section-title">コメント</h2>
        <div class="comment-section">
            <h3 class="comment-header">コメント ({{ $item->comments->count() }})</h3>
            <div class="comment-list">
                @foreach ($item->comments as $comment)
                <div class="comment">
                    <div class="comment-avatar">
                        <img src="{{ asset('storage/' . $comment->user->profile_image) }}" alt="{{ $comment->user->name }}">
                    </div>
                    <div class="comment-content">
                        <div class="comment-username">{{ $comment->user->name }}</div>
                        <div class="comment-text">{{ $comment->comment }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        @auth
        <h3 class="section-title">商品へのコメント</h3>
        <form action="{{ route('items.comment.store', $item->id) }}" method="POST">
            @csrf
            <textarea class="comment-input" name="comment" placeholder="コメントを入力してください"></textarea>
            <button type="submit" class="comment-submit-button">コメントを送信する</button>
        </form>
        @else
        <p>コメントを投稿するには <a href="{{ route('login') }}">ログイン</a> してください。</p>
        @endauth
    </div>
</div>
@endsection