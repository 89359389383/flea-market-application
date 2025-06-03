<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradeMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('trade_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('trades')->onDelete('cascade'); // 取引ID
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');    // 送信ユーザー
            $table->text('body');    // 本文
            $table->string('image_path')->nullable(); // 画像（任意）
            $table->boolean('is_read')->default(false); // 既読フラグ（新着通知用）
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trade_messages');
    }
}
