@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/index.css') }}" />
@endsection

@section('content')
<nav class="nav-tabs">
    <a href="#" class="nav-tab active">おすすめ</a>
    <a href="#" class="nav-tab">マイリスト</a>
</nav>

<div class="product-grid">
    @foreach ($items as $item)
    <div class="product-item">
        <a href="{{ route('items.show', $item->id) }}">
            <img
                src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : asset('storage/items/' . $item->image) }}"
                alt="{{ $item->name }}"
                class="product-image">
            <div class="product-info">
                <span class="product-name">{{ $item->name }}</span>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endsection