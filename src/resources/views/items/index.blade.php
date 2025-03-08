@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/index.css') }}" />
@endsection

@section('content')

<nav class="nav-tabs">
    <a href="{{ route('items.index') }}" class="nav-tab move-right {{ $tab == 'recommend' ? 'active' : '' }}">おすすめ</a>
    <!-- ⭐️【修正】マイリストに遷移する際に検索ワードを維持 -->
    <a href="{{ route('items.mylist', ['name' => request('name')]) }}" class="nav-tab {{ $tab == 'mylist' ? 'active' : '' }}">マイリスト</a>
</nav>

<div class="product-grid">
    @if ($tab == 'mylist')
    @if ($items->isEmpty())
    <p class="empty-message">マイリストに登録された商品はありません。</p>
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

@endsection