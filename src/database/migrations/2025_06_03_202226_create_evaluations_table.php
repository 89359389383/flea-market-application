<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsTable extends Migration
{
    public function up()
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained('trades')->onDelete('cascade'); // どの取引の評価か
            $table->foreignId('evaluator_id')->constrained('users')->onDelete('cascade'); // 評価した人
            $table->foreignId('evaluated_id')->constrained('users')->onDelete('cascade'); // 評価された人
            $table->tinyInteger('score'); // 評価点数
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('evaluations');
    }
}
