@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 商品購入ページ')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/show.css') }}" />
@endsection

@section('content')
<form action="{{ route('purchase.store', ['item_id' => $item->id]) }}" method="POST">
    @csrf
    <div class="container">
        <!-- メインコンテンツ（左側） -->
        <div class="main-content">
            <div class="product-header">
                <div class="product-image">
                    <img src="{{ filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : Storage::url($item->image) }}" class="product-image">
                </div>
                <div class="product-info">
                    <h1 class="product-title">{{ $item->name }}</h1>
                    <h2 class="product-price">¥{{ number_format($item->price) }}</h2>
                </div>
            </div>

            <!-- 支払い方法 -->
            <div class="section-payment">
                <h2 class="section-title">支払い方法</h2>
                <select name="payment_method" id="payment-method">
                    <option value="" selected class="default-option">選択してください</option>
                    <option value="コンビニ払い" class="payment-option">コンビニ払い</option>
                    <option value="カード払い" class="payment-option">カード払い</option>
                </select>
                @error('payment_method')
                <p class="error-message" style="color: red;">{{ $message }}</p>
                @enderror
            </div>

            <!-- 配送先 -->
            <div class="section-address">
                <div class="section-header">
                    <h2 class="section-title">配送先</h2>
                    <a href="{{ route('address.edit', ['item_id' => $item->id]) }}" class="change-address-link">変更する</a>
                </div>
                <div class="address">
                    <input type="hidden" name="postal_code" value="{{ $user->postal_code }}">
                    <input type="hidden" name="address" value="{{ $user->address }}">
                    <input type="hidden" name="building" value="{{ $user->building }}">
                    〒 {{ $user->postal_code }}<br>
                    {{ $user->address }}<br>
                    {{ $user->building }}
                </div>
            </div>
        </div>

        <!-- サイドバー（右側） -->
        <div class="sidebar">
            <div class="summary-box">
                <div class="summary-row">
                    <div class="summary-label">商品代金</div>
                    <div class="summary-value">¥{{ number_format($item->price) }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">支払い方法</div>
                    <div class="summary-value" id="selected-payment-method">選択してください</div>
                </div>
            </div>

            <form id="checkout-form" action="{{ route('stripe.checkout', ['item_id' => $item->id]) }}" method="GET">
                @csrf
                <button type="button" id="purchase-button" class="purchase-button">購入する</button>
            </form>
        </div>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const paymentSelect = document.getElementById("payment-method");
        const defaultOption = paymentSelect.querySelector("option[value='']");

        paymentSelect.addEventListener("focus", function() {
            // 「選択してください」を削除
            if (defaultOption) {
                defaultOption.remove();
            }
        });

        paymentSelect.addEventListener("change", function() {
            // 選択した値を反映
            document.getElementById("selected-payment-method").textContent = paymentSelect.value;
        });

        paymentSelect.addEventListener("blur", function() {
            // 何も選択せずに閉じた場合は「選択してください」を戻す
            if (!paymentSelect.value) {
                paymentSelect.insertAdjacentHTML("afterbegin", '<option value="" selected>選択してください</option>');
            }
        });

        document.getElementById("purchase-button").addEventListener("click", function(event) {
            const paymentMethod = paymentSelect.value;

            if (paymentMethod === "カード払い") {
                // 新しいフォームを送信
                document.getElementById("checkout-form").submit();
            } else if (paymentMethod === "コンビニ払い") {
                console.log("コンビニ払いが選択されました。purchase.storeを送信します。");
                document.querySelector("form[action*='purchase']").submit();
            } else {
                alert("支払い方法を選択してください");
            }
        });
    });
</script>
@endsection