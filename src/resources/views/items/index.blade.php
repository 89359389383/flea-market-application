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
    @for ($i = 0; $i < 7; $i++)
        <div class="product-item">
        <div class="product-image">商品画像</div>
        <div class="product-name">商品名</div>
</div>
@endfor
</div>
@endsection