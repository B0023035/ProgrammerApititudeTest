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
        if (! Schema::hasTable('exam_violations')) {
            Schema::create('exam_violations', function (Blueprint $table) {
                $table->id();

                // 外部キー
                $table->unsignedBigInteger('exam_session_id');
                $table->unsignedBigInteger('user_id');

                // 違反タイプ
                $table->string('violation_type', 50);

                // JSON詳細情報
                $table->json('violation_details')->nullable();

                // 検出日時
                $table->timestamp('detected_at')->useCurrent();

                $table->timestamps();

                // 外部キー制約
                $table->foreign('exam_session_id')
                    ->references('id')
                    ->on('exam_sessions')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                // インデックス
                $table->index(['exam_session_id', 'detected_at'], 'idx_session_detected');
                $table->index(['user_id', 'violation_type'], 'idx_user_violation_type');
                $table->index('violation_type', 'idx_violation_type');
                $table->index('detected_at', 'idx_detected_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
    }
};
