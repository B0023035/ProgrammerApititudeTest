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
        Schema::table('answers', function (Blueprint $table) {
            // 受験セッションIDを追加（既存の user_id とは別にセッション単位で管理）
            $table->unsignedBigInteger('exam_session_id')->after('user_id');

            // 外部キー制約（必要であれば）
            // $table->foreign('exam_session_id')->references('id')->on('exam_sessions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // 外部キー削除（有効化している場合）
            // $table->dropForeign(['exam_session_id']);

            $table->dropColumn('exam_session_id');
        });
    }
};
