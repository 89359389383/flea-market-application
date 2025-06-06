@extends('layouts.app')

@section('title', 'COACHTECHフリマ - ユーザープロフィール')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/show.css') }}">
@endsection

@section('content')
<div class="profile-header">
    <div class="profile-info">
        <!-- プロフィール画像 -->
        <div class="avatar">
            @if (Auth::user()->profile_image)
            <img src="{{ Storage::url(Auth::user()->profile_image) }}" alt="プロフィール画像" class="avatar-img">
            @else
            <img src="" alt="" class="avatar-img" style="display: none;">
            @endif
        </div>
        <h1 class="username">{{ Auth::user()->name }}</h1>
        {{-- ▼ 評価平均表示（評価がある場合のみ） --}}
        @if (isset($evaluations_count) && $evaluations_count > 0)
        @php $avg = round($average_score); @endphp
        <div class="rating" style="margin-top:10px;">
            @for ($i = 1; $i <= 5; $i++)
                <span class="star {{ $i <= $avg ? 'filled' : 'empty' }}">★</span>
                @endfor
                <span style="margin-left:8px;">
                    ({{ round($average_score) }} / 5) 評価{{ $evaluations_count }}件
                </span>
        </div>
        @endif
    </div>
    <a href="{{ route('user.edit') }}" class="edit-button">プロフィールを編集</a>
</div>

@php
$tab = request()->query('page', $tab ?? 'sell');

$user = Auth::user();
$unread_messages_total = 0;

if (isset($trades) && $trades instanceof \Illuminate\Support\Collection) {
$unread_messages_total = $trades->sum(function ($trade) use ($user) {
return $trade->messages()
->where('user_id', '!=', $user->id)
->where('is_read', false)
->count();
});
}
@endphp

<nav class="tabs">
    <a href="{{ route('user.show', ['page' => 'sell']) }}"
        class="tab move-right {{ $tab == 'sell' ? 'active' : '' }}">出品した商品</a>
    <a href="{{ route('user.show', ['page' => 'buy']) }}"
        class="tab {{ $tab == 'buy' ? 'active' : '' }}">購入した商品</a>
    <!-- ▼ tradingタブ：未読メッセージの合計件数を表示 -->
    <a href="{{ route('user.show', ['page' => 'trading']) }}"
        class="tab {{ $tab == 'trading' ? 'active' : '' }}">
        取引中の商品
        @if ($unread_messages_total > 0)
        <span class="tab-badge">{{ $unread_messages_total }}</span>
        @endif
    </a>
</nav>

<div class="product-grid">
    @if ($tab == 'buy')
    @foreach ($items as $item)
    <div class="product-item">
        <a href="{{ route('items.show', $item->id) }}">
            <div class="image-container">
                <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}" class="product-image">
                <div class="sold-label">Sold</div>
            </div>
            <div class="product-info">
                <span class="product-name">{{ $item->name }}</span>
            </div>
        </a>
    </div>
    @endforeach
    @elseif ($tab == 'trading')
    {{-- ▼ tradingタブ：取引中商品＋通知バッジ・未読メッセージ数 --}}
    @foreach ($trades as $trade)
    @php
    // 未読メッセージ数（自分以外から、かつ未読）
    $unread_count = $trade->messages()
    ->where('user_id', '!=', $user->id)
    ->where('is_read', false)
    ->count();
    // 総メッセージ件数
    $total_count = $trade->messages()->count();
    @endphp
    <div class="product-item">
        <a href="{{ route('trade.chat.show', $trade->id) }}">
            <div class="image-container" style="position:relative;">
                <img src="{{ filter_var($trade->item->image, FILTER_VALIDATE_URL) ? $trade->item->image : Storage::url($trade->item->image) }}" class="product-image">
                <div class="trading-label">取引中</div>
                @if ($unread_count > 0)
                <div class="notification-badge" style="position:absolute;top:0;left:0;">
                    {{ $unread_count }}
                </div>
                @endif
            </div>
            <div class="product-info">
                <span class="product-name">{{ $trade->item->name }}</span>
                <span class="message-count" style="margin-left:8px; font-size:12px;">メッセージ: {{ $total_count }}</span>
            </div>
        </a>
    </div>
    @endforeach
    @else
    {{-- ▼ sellタブ（出品商品） --}}
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
@endsection