@extends('layouts.app')

@section('title', 'COACHTECHフリマ - プロフィール設定')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/edit.css') }}" />
@endsection

@section('content')
<h1>プロフィール設定</h1>
<div class="profile-section">
    <div class="profile-image-container">
        <div class="profile-image"></div>
        <button class="image-select-button">画像を選択する</button>
    </div>
    <div class="input-group">
        <label class="input-label" for="username">ユーザー名</label>
        <input type="text" id="username" class="text-input">
    </div>
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