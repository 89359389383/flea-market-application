<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'evaluator_id',
        'evaluated_id',
        'score',
    ];

    // --- どの取引の評価か ---
    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    // --- 評価した人 ---
    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // --- 評価された人 ---
    public function evaluated()
    {
        return $this->belongsTo(User::class, 'evaluated_id');
    }
}
