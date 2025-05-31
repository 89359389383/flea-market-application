<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function items()
    {
        // このカテゴリは、item_categoryテーブルを通じて、たくさんのItemとつながっている
        // 中間テーブルで、category_idとitem_idを見る
        return $this->belongsToMany(Item::class, 'item_category', 'category_id', 'item_id');
    }
}
