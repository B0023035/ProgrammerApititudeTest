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
        Schema::table('exam_sessions', function (Blueprint $table) {
            // UUID型のカラムを追加（NULL許可、一意制約付き）
            $table->uuid('session_uuid')->nullable()->unique()->after('id');

            // インデックスを追加してクエリパフォーマンスを向上
            $table->index('session_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // インデックスを削除
            $table->dropIndex(['session_uuid']);

            // カラムを削除
            $table->dropColumn('session_uuid');
        });
    }
};
