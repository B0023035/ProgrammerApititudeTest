<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->tinyInteger('current_part')->default(1);
            $table->tinyInteger('current_question')->default(1);
            $table->integer('remaining_time')->default(0); // 秒数
            $table->timestamps();
            
            // インデックス
            $table->index(['user_id', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};