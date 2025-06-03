<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'seller_id',
        'buyer_id',
        'is_completed',
    ];

    // --- 商品 ---
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // --- 出品者 ---
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // --- 購入者 ---
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // --- チャットメッセージ ---
    public function messages()
    {
        return $this->hasMany(TradeMessage::class);
    }

    // --- 取引に紐づく評価（両方の評価）---
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}
