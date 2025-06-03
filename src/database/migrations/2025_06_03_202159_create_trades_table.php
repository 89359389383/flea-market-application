<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade'); // 取引対象の商品
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade'); // 出品者
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');  // 購入者
            $table->boolean('is_completed')->default(false); // 取引完了フラグ
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trades');
    }
}
