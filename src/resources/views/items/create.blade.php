@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品出品')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item/create.css') }}">
@endsection

@section('content')
<main class="main-content">
    <h1 class="page-title">商品の出品</h1>
    <!-- 出品フォーム -->
    <form class="product-form" action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- 商品画像アップロードセクション -->
        <h2 class="product-photo">商品画像</h2>
        <div class="product-image-section">
            <!-- プレビュー表示エリア -->
            <div class="image-preview">
                <img id="image-preview" style="display: none;" alt="商品画像プレビュー">
            </div>

            <!-- ファイル入力 -->
            <input type="file" id="product-image" class="product-image-input" name="image" accept="image/*">
            <label for="product-image" class="choose-image-button">画像を選択する</label>
            @error('image')
            <p class="error-message" style="color: red;">{{ $message }}</p>
            @enderror
        </div>

        <!-- 商品の詳細 -->
        <div class="product-details">
            <h2 class="details-title">商品の詳細</h2>

            <!-- カテゴリ -->
            <div class="product-category">
                <p class="category-label">カテゴリー</p>
                <div class="category-tags">
                    @foreach($categories as $category)
                    <span class="category-tag" data-category="{{ $category->id }}">{{ $category->name }}</span>
                    @endforeach
                </div>
                <!-- 選択されたカテゴリーを送信 -->
                <input type="hidden" name="categories[]" id="selected-categories">
                @error('categories.0')
                <p class="error-message" style="color: red;">{{ $message }}</p>
                @enderror

            </div>

            <!-- 商品の状態 -->
            <div class="product-condition">
                <label for="product-state" class="condition-label">商品の状態</label>
                <select id="product-state" name="condition" class="condition-select">
                    <option value="">選択してください</option>
                    <option value="良好">良好</option>
                    <option value="目立った傷や汚れなし">目立った傷や汚れなし</option>
                    <option value="やや傷や汚れあり">やや傷や汚れあり</option>
                    <option value="状態が悪い">状態が悪い</option>
                </select>
                @error('condition')
                <p class="error-message" style="color: red;">{{ $message }}</p>
                @enderror
            </div>

            <div class="product-form-section">
                <h2 class="description-title">商品名と説明</h2>
                <div class="product-form-group">
                    <div class="label">商品名</div>
                    <input type="text" name="name" class="product-form-input" placeholder="商品名を入力してください" value="{{ old('name') }}">
                    @error('name')
                    <p class="error-message" style="color: red;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="product-form-group">
                    <div class="label">ブランド名</div>
                    <input type="text" name="brand_name" class="product-form-input" placeholder="ブランド名を入力してください">
                    @error('brand_name')
                    <p class="error-message" style="color: red;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="product-form-group">
                    <div class="label">商品の説明</div>
                    <textarea name="description" class="product-form-input" placeholder="商品の説明を入力してください">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="error-message" style="color: red;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="product-form-group">
                    <div class="label">販売価格</div>
                    <div class="price-input">
                        <input type="text" name="price" class="product-form-input" placeholder="¥0" value="¥{{ old('price', 0) }}">
                        @error('price')
                        <p class="error-message" style="color: red;">{!! nl2br(e($message)) !!}</p>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="submit-button">出品する</button>
            </div>
        </div>
    </form>
</main>

<!-- 画像プレビュー & カテゴリー選択用スクリプト -->
<script>
    document.getElementById('product-image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('image-preview');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block'; // 画像が選択されたら表示
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none'; // ファイルが選択されていない場合は非表示
        }
    });

    document.querySelectorAll('.category-tag').forEach(tag => {
        tag.addEventListener('click', function() {
            this.classList.toggle('selected');

            // 選択されたカテゴリーのIDを取得（数値に変更）
            const selectedCategories = Array.from(document.querySelectorAll('.category-tag.selected'))
                .map(tag => tag.dataset.category); // category_id を取得（数値）

            // hidden input にセット
            document.getElementById('selected-categories').value = selectedCategories.join(',');

            // Ensure that if no category is selected, we send an empty string instead of null
            if (selectedCategories.length === 0) {
                document.getElementById('selected-categories').value = ''; // Set to empty string
            }
        });
    });
</script>

@endsection