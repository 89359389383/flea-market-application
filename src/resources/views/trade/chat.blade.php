<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>取引チャット</title>
    <link rel="stylesheet" href="{{ asset('css/trade/chat.css') }}">
    <style>
        /* 必要な部分だけ抜粋。実際は外部CSSに分離を推奨 */
        .user-message {
            text-align: right;
            margin-left: auto;
        }

        .partner-message {
            text-align: left;
            margin-right: auto;
        }

        .message-bubble {
            display: inline-block;
            padding: 10px;
            border-radius: 12px;
            background: #f7f7ff;
            margin-bottom: 2px;
        }

        .user-message .message-bubble {
            background: #c2e9fb;
        }

        .message-actions {
            font-size: 12px;
        }

        .avatar-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .star.active {
            color: gold;
        }

        .star {
            cursor: pointer;
            font-size: 24px;
        }

        .badge {
            background: #d33;
            color: #fff;
            border-radius: 10px;
            font-size: 11px;
            padding: 1px 7px;
            margin-left: 4px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
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
        <div class="header" style="display:flex;align-items:center;">
            <div class="avatar">
                @if (!empty($partner->profile_image))
                <img src="{{ asset('storage/' . $partner->profile_image) }}" class="avatar-img" alt="">
                @endif
            </div>
            <h1 class="header-title" style="margin-left:15px;">
                「{{ $partner->name }}」さんとの取引画面
            </h1>
        </div>

        <div class="product-section" style="display:flex;align-items:center;">
            <div class="product-image">
                <img src="{{ filter_var($trade->item->image, FILTER_VALIDATE_URL) ? $trade->item->image : Storage::url($trade->item->image) }}" class="product-image-thumb" alt="商品画像">
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
            <div class="user-message">
                <div class="user-message-header" style="display:flex;justify-content:flex-end;align-items:center;">
                    <span class="username">{{ $msg->user->name }}</span>
                    @if (!empty($msg->user->profile_image))
                    <img src="{{ asset('storage/' . $msg->user->profile_image) }}" class="avatar-img" style="margin-left:5px;" alt="">
                    @endif
                </div>
                <div class="message-bubble">
                    {{ $msg->body }}
                    @if($msg->image_path)
                    <br>
                    <img src="{{ Storage::url($msg->image_path) }}" style="max-width:100px;" alt="添付画像">
                    @endif
                </div>
                <div class="message-actions">
                    <a href="#" class="edit-link" data-id="{{ $msg->id }}" data-body="{{ $msg->body }}">編集</a>
                    <form action="{{ route('trade.message.destroy', $msg->id) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;color:#b00;">削除</button>
                    </form>
                </div>
            </div>
            @else
            <div class="partner-message">
                <div class="message-header" style="display:flex;align-items:center;">
                    @if (!empty($msg->user->profile_image))
                    <img src="{{ asset('storage/' . $msg->user->profile_image) }}" class="avatar-img" alt="">
                    @endif
                    <span class="username" style="margin-left:5px;">{{ $msg->user->name }}</span>
                </div>
                <div class="message-bubble">
                    {{ $msg->body }}
                    @if($msg->image_path)
                    <br>
                    <img src="{{ Storage::url($msg->image_path) }}" style="max-width:100px;" alt="添付画像">
                    @endif
                </div>
            </div>
            @endif
            @empty
            <div style="text-align:center;color:#888;margin:30px;">メッセージはまだありません。</div>
            @endforelse
        </div>

        {{-- エラー表示 --}}
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $e)
            <div>{{ $e }}</div>
            @endforeach
        </div>
        @endif

        {{-- 新規投稿フォーム --}}
        <form action="{{ route('trade.message.store', $trade->id) }}" method="POST" enctype="multipart/form-data" class="input-section" id="chat-form">
            @csrf
            <input type="text" class="message-input" name="body" id="chat-body"
                value="{{ session('chat_body_' . $trade->id, old('body')) }}"
                placeholder="取引メッセージを記入してください">
            <input type="file" name="image" style="display:none;" id="image-input">
            <button type="button" class="add-image-btn" onclick="document.getElementById('image-input').click();">画像を追加</button>
            <button type="submit" class="send-btn" aria-label="送信">
                <svg class="send-icon" viewBox="0 0 40 40">
                    <polygon points="5,35 35,20 5,5 5,18 27,20 5,22"
                        style="fill:none;stroke:#888;stroke-width:2" />
                </svg>
            </button>
            {{-- 画像プレビュー用 --}}
            <div class="image-preview" style="margin-top: 8px;">
                <img id="chat-image-preview" style="display:none; max-width:100px; max-height:100px;" alt="画像プレビュー">
            </div>
            @error('body')
            <p class="error-message" style="color:red;">{{ $message }}</p>
            @enderror
            @error('image')
            <p class="error-message" style="color:red;">{{ $message }}</p>
            @enderror
        </form>

        {{-- 編集用モーダル（隠しフォーム）--}}
        <div id="edit-modal" style="display:none;position:fixed;top:20%;left:0;right:0;z-index:20;background:rgba(0,0,0,.2);">
            <div style="background:#fff;margin:0 auto;padding:25px;border-radius:10px;width:320px;">
                <form method="POST" id="edit-form">
                    @csrf
                    @method('PUT')
                    {{-- ▼ エラーメッセージ表示（body） --}}
                    @error('body')
                    <p class="error-message" style="color:red;">{{ $message }}</p>
                    @enderror
                    <textarea name="body" id="edit-body" style="width:100%;height:70px;" required maxlength="400">{{ old('body') }}</textarea>
                    <button type="submit" class="send-btn" style="width:100%;">編集して送信</button>
                    <button type="button" onclick="document.getElementById('edit-modal').style.display='none'" style="margin-top:6px;width:100%;">キャンセル</button>
                </form>
            </div>
        </div>

        {{-- 取引完了ボタン・モーダル（評価済みなら非表示） --}}
        {{-- ここからBlade条件組み込み --}}
        @if(
        !$trade->is_completed &&
        (
        // 購入者の場合：自分が未評価
        ($trade->buyer_id == auth()->id() && empty($alreadyEvaluated))
        ||
        // 出品者の場合：相手(購入者)が評価済み、かつ自分が未評価
        ($trade->seller_id == auth()->id() && !empty($partnerEvaluated) && empty($alreadyEvaluated))
        )
        )
        <div style="margin-top:30px;text-align:center;">
            <button class="send-button" onclick="document.getElementById('complete-modal').style.display='block'">取引を完了する</button>
        </div>
        @endif
        {{-- ここまでBlade条件組み込み --}}

        <div id="complete-modal" class="transaction-complete" style="display:none;">
            <div class="complete-header">取引が完了しました。</div>
            <form action="{{ route('trade.evaluate.store', $trade->id) }}" method="POST">
                @csrf
                <div class="rating-section">
                    <div class="rating-question">今回の取引相手はどうでしたか？</div>
                    <div class="star-rating" id="star-area">
                        @for($i=1; $i<=5; $i++)
                            <span class="star" data-score="{{ $i }}">★</span>
                            @endfor
                    </div>
                    <input type="hidden" name="score" id="star-score" value="5">
                </div>
                <div class="button-section">
                    <button type="submit" class="send-button">送信する</button>
                    <button type="button" onclick="document.getElementById('complete-modal').style.display='none'" style="margin-left:10px;">キャンセル</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 編集リンククリック時：モーダル開く＋本文セット＋action絶対パス設定
        document.querySelectorAll('.edit-link').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('edit-modal').style.display = 'block';
                document.getElementById('edit-body').value = el.dataset.body;
                // 絶対パスでactionセット（urlヘルパー利用）
                document.getElementById('edit-form').action =
                    "{{ url('/trade-message') }}/" + el.dataset.id;
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

        // メッセージ送信後の入力欄クリア（form.resetで値リセット）
        document.getElementById('chat-form').addEventListener('submit', function() {
            setTimeout(() => this.reset(), 100); // 送信後クリア
        });

        // 画像選択時のプレビュー表示
        document.getElementById('image-input').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('chat-image-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        });

        // ▼ 追加：ローカルストレージで本文の入力保持機能 ▼
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('chat-body');
            const key = 'chat_body_{{ $trade->id }}';

            // 入力時にローカルストレージに保存
            input.addEventListener('input', function() {
                localStorage.setItem(key, input.value);
            });

            // ページ読み込み時に復元
            if (localStorage.getItem(key)) {
                input.value = localStorage.getItem(key);
            }

            // 送信時にローカルストレージをクリア
            document.getElementById('chat-form').addEventListener('submit', function() {
                localStorage.removeItem(key);
                setTimeout(() => input.value = '', 100);
            });
        });
    </script>
</body>

</html>