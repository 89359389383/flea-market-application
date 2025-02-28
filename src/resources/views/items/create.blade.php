@extends('layout')

@section('title', 'COACHTECHフリマ - 商品出品')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/create.css') }}">
@endsection

@section('content')
<main class="main-content">
    <h1 class="page-title">商品の出品</h1>

    <!-- 出品フォーム -->
    <form class="product-form">
        <!-- 商品画像アップロードセクション -->
        <div class="product-image-section">
            <div class="product-image-label">商品画像</div>
            <input type="file" id="product-image" class="product-image-input" />
            <label for="product-image" class="choose-image-button">画像を選択する</label>
        </div>

        <!-- 商品の詳細 -->
        <div class="product-details">
            <h2 class="details-title">商品の詳細</h2>

            <!-- カテゴリ -->
            <div class="product-category">
                <p class="category-label">カテゴリー</p>
                <div class="category-tags">
                    <span class="category-tag">ファッション</span>
                    <span class="category-tag">家電</span>
                    <span class="category-tag">インテリア</span>
                    <span class="category-tag">レディース</span>
                    <span class="category-tag">メンズ</span>
                    <span class="category-tag">コスメ</span>
                    <span class="category-tag">本</span>
                    <span class="category-tag">ゲーム</span>
                    <span class="category-tag">スポーツ</span>
                    <span class="category-tag">キッチン</span>
                    <span class="category-tag">ハンドメイド</span>
                    <span class="category-tag">アクセサリー</span>
                    <span class="category-tag">おもちゃ</span>
                    <span class="category-tag">ベビー・キッズ</span>
                </div>
            </div>

            <!-- 商品の状態 -->
            <div class="product-condition">
                <label for="product-state" class="condition-label">商品の状態</label>
                <select id="product-state" class="condition-select">
                    <option value="">選択してください</option>
                    <option value="new">新品・未使用</option>
                    <option value="like-new">未使用に近い</option>
                    <option value="used">やや傷や汚れあり</option>
                    <option value="bad">傷や汚れが多い</option>
                </select>
            </div>

            <div class="product-form-section">
                <h2>商品名と説明</h2>
                <div class="product-form-group">
                    <label>商品名</label>
                    <input type="text" class="product-form-input" placeholder="商品名を入力してください">
                </div>

                <div class="product-form-group">
                    <label>ブランド名</label>
                    <input type="text" class="product-form-input" placeholder="ブランド名を入力してください">
                </div>

                <div class="product-form-group">
                    <label>商品の説明</label>
                    <textarea class="product-form-input" placeholder="商品の説明を入力してください"></textarea>
                </div>

                <div class="product-form-group">
                    <label>販売価格</label>
                    <div class="price-input">
                        <span>¥</span>
                        <input type="number" class="product-form-input" placeholder="0">
                    </div>
                </div>
                <button type="submit" class="submit-button">出品する</button>
            </div>
        </div>
    </form>
</main>
@endsection