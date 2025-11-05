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
        Schema::table('choices', function (Blueprint $table) {
            // part列をenum型で追加（1, 2, 3）
            $table->enum('part', ['1', '2', '3'])
                ->after('question_id')
                ->comment('問題の部を示す（1～3）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('choices', function (Blueprint $table) {
            $table->dropColumn('part');
        });
    }
};
