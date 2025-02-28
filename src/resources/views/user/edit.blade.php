@extends('layouts.app')

@section('title', 'COACHTECHフリマ - プロフィール設定')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/edit.css') }}" />
@endsection

@section('content')
<h1>プロフィール設定</h1>
<div class="profile-section">
    <form action="{{ route('user.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="profile-image-container">
            <div class="profile-image">
                @if ($user->profile_image)
                <img src="{{ asset('storage/' . $user->profile_image) }}" alt="プロフィール画像" class="profile-preview">
                @else
                <p>" "</p>
                @endif
            </div>
            <input type="file" name="profile_image" class="image-select-button">
        </div>

        <div class="input-group">
            <label class="input-label" for="name">ユーザー名</label>
            <input type="text" id="name" name="name" class="text-input"
                value="{{ old('name', $user->name) }}">
        </div>

        <div class="input-group">
            <label class="input-label" for="postal_code">郵便番号</label>
            <input type="text" id="postal_code" name="postal_code" class="text-input"
                value="{{ old('postal_code', $user->postal_code) }}">
        </div>

        <div class="input-group">
            <label class="input-label" for="address">住所</label>
            <input type="text" id="address" name="address" class="text-input"
                value="{{ old('address', $user->address) }}">
        </div>

        <div class="input-group">
            <label class="input-label" for="building_name">建物名</label>
            <input type="text" id="building_name" name="building_name" class="text-input"
                value="{{ old('building_name', $user->building) }}">
        </div>

        <button type="submit" class="submit-button">更新する</button>
    </form>
</div>
@endsection