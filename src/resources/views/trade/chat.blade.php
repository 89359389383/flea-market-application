@extends('layouts.app')

@section('title', 'COACHTECHフリマ - 取引チャットページ')

@section('css')
<link rel="stylesheet" href="{{ asset('css/trade/chat.css') }}" />
@endsection

@section('content')
<div class="container">
    <div class="sidebar">
        <h2>その他の取引</h2>
        @foreach($other_trades as $other)
        @php
        // 自分宛ての未読メッセージのみカウント（正確な未読バッジ件数）
        $unread = $other->messages->where('is_read', false)
        ->where('user_id', '!=', auth()->id())->count();
        @endphp
        <form action="{{ route('trade.chat.show', $other->id) }}" method="get" style="margin-bottom:6px;">
            <button class="product-button @if($other->id === $trade->id) active @endif" type="submit">
                <div class="product-name">{{ $other->item->name }}</div>
                @if($unread)
                <span class="badge">{{ $unread }}</span>
                @endif
            </button>
        </form>
        @endforeach
    </div>

    <div class="main-content">
        {{-- ヘッダー --}}
        <div class="trade-chat-header" style="display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center;">
                <div class="avatar">
                    @if (!empty($partner->profile_image))
                    <img src="{{ asset('storage/' . $partner->profile_image) }}" class="avatar-img" alt="">
                    @endif
                </div>
                <h1 class="header-title" style="margin-left:15px;">
                    「{{ $partner->name }}」さんとの取引画面
                </h1>
            </div>

            @if(!$trade->is_completed && (
            ($trade->buyer_id == auth()->id() && empty($alreadyEvaluated))
            ||
            ($trade->seller_id == auth()->id() && !empty($partnerEvaluated) && empty($alreadyEvaluated))
            ))
            <button class="send-button" onclick="document.getElementById('complete-modal').style.display='flex'">
                取引を完了する
            </button>
            @endif
        </div>

        {{-- 商品セクション --}}
        <div class="product-section" style="display:flex;align-items:center;">
            <div class="product-image">
                <img src="{{ filter_var($trade->item->image, FILTER_VALIDATE_URL) ? $trade->item->image : Storage::url($trade->item->image) }}"
                    class="product-image-thumb" alt="商品画像">
            </div>
            <div class="product-info" style="margin-left:15px;">
                <div class="trade-chat-item-name">{{ $trade->item->name }}</div>
                <div class="product-price">¥{{ number_format($trade->item->price) }}</div>
            </div>
        </div>

        {{-- メッセージ一覧 --}}
        <div class="chat-section">
            @forelse($messages as $msg)
            @if($msg->user_id === auth()->id())
            <div class="user-message comment" style="flex-direction: column; align-items: flex-end;">
                <div class="comment-content" style="display: flex; align-items: center; flex-direction: row-reverse;">
                    <div class="comment-avatar">
                        @if (!empty($msg->user->profile_image))
                        <img src="{{ asset('storage/' . $msg->user->profile_image) }}" class="comment-avatar-img">
                        @endif
                    </div>
                    <div class="comment-username" style="margin-right: 20px; margin-left: 0;">
                        {{ $msg->user->name }}
                    </div>
                </div>
                <div class="comment-text user-comment-text">
                    {{ $msg->body }}
                    @if($msg->image_path)
                    <br>
                    <img src="{{ Storage::url($msg->image_path) }}" class="chat-image-thumb" alt="添付画像">
                    @endif
                </div>
                {{-- メッセージの編集・削除アクション --}}
                @if(!$trade->is_completed)
                <div class="message-actions" style="justify-content: flex-end; width: 40%; margin-right: 0;">
                    <a href="#" class="edit-link message-edit" data-id="{{ $msg->id }}" data-body="{{ $msg->body }}">編集</a>
                    <form action="{{ route('trade.message.destroy', $msg->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="message-delete" style="background:none;border:none;">削除</button>
                    </form>
                </div>
                @endif
            </div>
            @else
            <div class="partner-message comment">
                <div class="comment-content">
                    <div class="comment-avatar">
                        @if (!empty($msg->user->profile_image))
                        <img src="{{ asset('storage/' . $msg->user->profile_image) }}" class="comment-avatar-img">
                        @endif
                    </div>
                    <div class="comment-username">{{ $msg->user->name }}</div>
                </div>
                <div class="comment-text">
                    {{ $msg->body }}
                    @if($msg->image_path)
                    <br>
                    <img src="{{ Storage::url($msg->image_path) }}" class="chat-image-thumb" alt="添付画像">
                    @endif
                </div>
            </div>
            @endif
            @empty
            <div style="text-align:center;color:#888;margin:30px;">メッセージはまだありません。</div>
            @endforelse
        </div>

        {{-- エラー表示（フォーム上部にまとめて表示） --}}
        @if($errors->any())
        <div class="alert alert-danger" style="color:red; margin: 10px 20px; font-weight: bold;">

            @foreach($errors->all() as $e)
            <div>{{ $e }}</div>
            @endforeach
        </div>
        @endif

        {{-- 新規投稿フォーム --}}
        @if(!$trade->is_completed)
        <form action="{{ route('trade.message.store', $trade->id) }}" method="POST" enctype="multipart/form-data" class="input-section" id="chat-form">
            @csrf
            <input type="text" class="message-input" name="body" id="chat-body"
                value="{{ session('chat_body_' . $trade->id, old('body')) }}" placeholder="取引メッセージを記入してください">
            <input type="file" name="image" style="display:none;" id="image-input">
            <button type="button" class="add-image-btn" onclick="document.getElementById('image-input').click();">画像を追加</button>
            <button type="submit" class="send-btn" aria-label="送信">
                <svg class="send-icon" viewBox="0 0 40 40">
                    <polygon points="5,35 35,20 5,5 5,18 27,20 5,22" style="fill:none;stroke:#888;stroke-width:2" />
                </svg>
            </button>

            {{-- 画像プレビュー部分に×ボタン追加 --}}
            <div class="image-preview" style="margin-top: 8px; position: relative;">
                <img id="chat-image-preview" alt="画像プレビュー" style="display:none; max-width:160px; max-height:160px; border-radius:10px;">
                <button id="remove-preview-btn" type="button" style="display:none; position:absolute; top:-8px; right:-8px; background:#fff; color:#888; border:none; font-size:20px; width:28px; height:28px; border-radius:50%; box-shadow:0 2px 6px #aaa; cursor:pointer;">×</button>
            </div>
        </form>
        @endif

        {{-- 編集用モーダル（隠しフォーム）--}}
        <div id="edit-modal" class="edit-modal">
            <div class="edit-modal-content">
                <form method="POST" id="edit-form">
                    @csrf
                    @method('PUT')
                    {{-- ▼ エラーメッセージ表示（body） --}}
                    @error('body')
                    <p class="error-message" style="color:red;">{{ $message }}</p>
                    @enderror
                    <textarea name="body" id="edit-body" class="edit-modal-textarea" required maxlength="400">{{ old('body') }}</textarea>
                    <button type="submit" class="send-btn edit-modal-submit">編集して送信</button>
                    <button type="button" class="edit-modal-cancel" onclick="document.getElementById('edit-modal').style.display='none'">キャンセル</button>
                </form>
            </div>
        </div>

        {{-- 取引完了モーダル（中央・サンプル風デザイン） --}}
        <div id="complete-modal" class="transaction-complete" style="display:none;">
            <div class="transaction-complete-content">
                <div class="complete-header">取引が完了しました。</div>
                <form action="{{ route('trade.evaluate.store', $trade->id) }}" method="POST">
                    @csrf
                    <div class="rating-section">
                        <div class="rating-question">今回の取引相手はどうでしたか？</div>
                        <div class="star-rating" id="star-area">
                            @for($i=1; $i<=5; $i++) <span class="star" data-score="{{ $i }}">★</span> @endfor
                        </div>
                        <input type="hidden" name="score" id="star-score" value="5">
                    </div>
                    <div class="button-section">
                        <button type="submit" class="send-button">送信する</button>
                        <button type="button" class="cancel-button" onclick="document.getElementById('complete-modal').style.display='none'">キャンセル</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // 編集リンククリック時：モーダル開く＋本文セット＋action絶対パス設定
            document.querySelectorAll('.edit-link').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    const editModal = document.getElementById('edit-modal');
                    editModal.style.display = 'flex'; // ここがポイント！
                    document.getElementById('edit-body').value = el.dataset.body;
                    document.getElementById('edit-form').action = "{{ url('/trade-message') }}/" + el.dataset.id;
                });
            });

            // 編集モーダルのキャンセルボタン押下時に閉じる処理
            document.querySelectorAll('.edit-modal-cancel').forEach(button => {
                button.addEventListener('click', () => {
                    document.getElementById('edit-modal').style.display = 'none';
                });
            });

            // スター評価クリック時の表示切替
            document.querySelectorAll('.star').forEach(function(star) {
                star.onclick = function() {
                    let score = this.dataset.score;
                    document.getElementById('star-score').value = score;
                    document.querySelectorAll('.star').forEach(function(s, i) {
                        s.className = 'star' + (i < score ? ' active' : '');
                    });
                }
            });

            // メッセージ送信後の入力欄クリア
            document.getElementById('chat-form').addEventListener('submit', function() {
                setTimeout(() => this.reset(), 100);
            });

            // 画像選択時プレビュー表示
            const imageInput = document.getElementById('image-input');
            const preview = document.getElementById('chat-image-preview');
            const removeBtn = document.getElementById('remove-preview-btn');

            imageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                        removeBtn.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                    removeBtn.style.display = 'none';
                }
            });

            // ×ボタンでプレビュー非表示＆inputリセット
            removeBtn.addEventListener('click', function() {
                preview.src = '';
                preview.style.display = 'none';
                removeBtn.style.display = 'none';
                imageInput.value = '';
            });

            // ローカルストレージで本文の入力保持
            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('chat-body');
                const key = 'chat_body_{{ $trade->id }}';

                input.addEventListener('input', function() {
                    localStorage.setItem(key, input.value);
                });

                if (localStorage.getItem(key)) {
                    input.value = localStorage.getItem(key);
                }

                document.getElementById('chat-form').addEventListener('submit', function() {
                    localStorage.removeItem(key);
                    setTimeout(() => input.value = '', 100);
                });
            });
        </script>
        </body>

        </html>
    </div>
</div>
@endsection