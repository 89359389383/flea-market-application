@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 住所変更')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase/address_edit.css') }}" />
@endsection

@section('content')
<h1>住所の変更</h1>
<div class="profile-section">
    <div class="input-group">
        <label class="input-label" for="postal_code">郵便番号</label>
        <input type="text" id="postal_code" class="text-input">
    </div>
    <div class="input-group">
        <label class="input-label" for="address">住所</label>
        <input type="text" id="address" class="text-input">
    </div>
    <div class="input-group">
        <label class="input-label" for="building_name">建物名</label>
        <input type="text" id="building_name" class="text-input">
    </div>

    <button type="submit" class="submit-button">更新する</button>
</div>
@endsection