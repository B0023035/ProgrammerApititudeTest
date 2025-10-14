<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 実行時にテーブルを作成
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('イベント名');
            $table->string('passphrase')->comment('受験パスフレーズ');
            $table->dateTime('begin')->comment('パスフレーズ有効開始日時');
            $table->dateTime('end')->comment('パスフレーズ有効終了日時');
            $table->enum('exam_type', ['30min', '45min', 'full'])
                  ->default('full')
                  ->comment('出題形式（試験バージョン）');
            $table->timestamps();
        });
    }

    /**
     * ロールバック時にテーブルを削除
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
