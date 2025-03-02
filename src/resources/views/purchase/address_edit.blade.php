@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 住所変更')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/address_edit.css') }}" />
@endsection

@section('content')
<h1>住所の変更</h1>
<form action="{{ route('address.update', ['item_id' => $item_id]) }}" method="POST">
    @csrf
    <div class="profile-section">
        <div class="input-group">
            <label class="input-label" for="postal_code">郵便番号</label>
            <input type="text" id="postal_code" name="postal_code" class="text-input" value="{{ old('postal_code', $address['postal_code'] ?? '') }}">
            @error('postal_code')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>
        <div class="input-group">
            <label class="input-label" for="address">住所</label>
            <input type="text" id="address" name="address" class="text-input" value="{{ old('address', $address['address'] ?? '') }}">
            @error('address')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>
        <div class="input-group">
            <label class="input-label" for="building_name">建物名</label>
            <input type="text" id="building_name" name="building" class="text-input" value="{{ old('building_name', $address['building'] ?? '') }}">
            @error('building')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <button type="submit" class="submit-button">更新する</button>
    </div>
</form>
@endsection