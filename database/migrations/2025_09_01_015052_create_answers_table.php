<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('question_id');
            $table->tinyInteger('part'); // 1,2,3
            $table->enum('choice', ['A','B','C','D','E'])->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']); // 回答の重複防止
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
