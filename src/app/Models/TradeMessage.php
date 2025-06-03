<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'user_id',
        'body',
        'image_path',
        'is_read',
    ];

    // --- どの取引のチャットか ---
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    // --- 投稿ユーザー ---
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
