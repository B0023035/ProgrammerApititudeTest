<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('choices', function (Blueprint $table) {
            $table->id();

            // 外部キー：questions.id に紐付け、問題削除時に選択肢も削除
            $table->foreignId('question_id')
                ->constrained('questions')
                ->onDelete('cascade');

            // 選択肢ラベル
            $table->enum('label', ['A', 'B', 'C', 'D', 'E']);

            // テキスト選択肢（第一部・第三部用）
            $table->text('text')->nullable();

            // 画像選択肢（第二部用）
            $table->string('image')->nullable();

            // 正解フラグ
            $table->boolean('is_correct')->default(false);

            // 作成・更新日時
            $table->timestamps();

            // 同じ問題に同じラベルが入らないようにユニーク制約
            $table->unique(['question_id', 'label']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('choices');
    }
};
