<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('part'); // 1,2,3 部番号
            $table->text('text')->nullable(); // 問題文（テキスト問題用）
            $table->string('image')->nullable(); // 問題画像の URL（第二部用）
            $table->timestamps();
            $table->integer('number'); // 部内での問題番号
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
