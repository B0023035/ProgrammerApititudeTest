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
        Schema::create('event_questions', function (Blueprint $table) {
            $table->id();
            
            // イベントID
            $table->foreignId('event_id')
                ->constrained('events')
                ->onDelete('cascade');
            
            // 問題ID
            $table->foreignId('question_id')
                ->constrained('questions')
                ->onDelete('cascade');
            
            // イベント内での出題順
            $table->integer('order')->default(0);
            
            $table->timestamps();
            
            // 同じイベントに同じ問題は1回のみ
            $table->unique(['event_id', 'question_id']);
            
            // 検索用インデックス
            $table->index(['event_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_questions');
    }
};
