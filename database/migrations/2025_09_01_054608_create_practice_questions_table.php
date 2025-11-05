<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('practice_questions', function (Blueprint $table) {
            $table->id(); // 練習問題ID
            $table->unsignedTinyInteger('part'); // 部番号（1,2,3...）
            $table->text('text'); // 問題文
            $table->string('image')->nullable(); // 画像問題用
            $table->timestamps();
        });

        Schema::create('practice_choices', function (Blueprint $table) {
            $table->id(); // 練習選択肢ID
            $table->foreignId('question_id')->constrained('practice_questions')->onDelete('cascade'); // 紐付く問題
            $table->enum('label', ['A', 'B', 'C', 'D', 'E']); // 選択肢ラベル
            $table->text('text')->nullable(); // 選択肢のテキスト
            $table->string('image')->nullable(); // 画像パス
            $table->boolean('is_correct')->default(0); // 正解フラグ
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_questions');
    }
};
