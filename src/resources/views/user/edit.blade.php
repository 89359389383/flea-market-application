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
                <img id="image-preview" src="{{ asset('storage/' . $user->profile_image) }}" alt="プロフィール画像" class="profile-preview">
                @else
                <img id="image-preview" src="" alt="プロフィール画像" class="profile-preview" style="display: none;">
                @endif
            </div>
            <div class="custom-file-container">
                <label for="profile-image-input" class="custom-file-label">画像を選択する</label>
                <input type="file" id="profile-image-input" name="profile_image" class="image-select-button">
            </div>
            @error('profile_image')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <div class="input-group">
            <label class="input-label" for="name">ユーザー名</label>
            <input type="text" id="name" name="name" class="text-input"
                value="{{ old('name', $user->name) }}">
            @error('name')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <div class="input-group">
            <label class="input-label" for="postal_code">郵便番号</label>
            <input type="text" id="postal_code" name="postal_code" class="text-input"
                value="{{ old('postal_code', $user->postal_code) }}">
            @error('postal_code')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <div class="input-group">
            <label class="input-label" for="address">住所</label>
            <input type="text" id="address" name="address" class="text-input"
                value="{{ old('address', $user->address) }}">
            @error('address')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <div class="input-group">
            <label class="input-label" for="building">建物名</label>
            <input type="text" id="building" name="building" class="text-input"
                value="{{ old('building', $user->building) }}">
            @error('building')
            <p class="error-message" style="color: red;">
                {{ $message }}
            </p>
            @enderror
        </div>

        <button type="submit" class="submit-button">更新する</button>
    </form>
</div>

<script>
    document.getElementById('profile-image-input').addEventListener('change', function(event) {
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
</script>
@endsection