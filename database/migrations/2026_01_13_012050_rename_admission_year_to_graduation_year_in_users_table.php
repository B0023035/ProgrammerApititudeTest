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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admission_year') && !Schema::hasColumn('users', 'graduation_year')) {
                $table->renameColumn('admission_year', 'graduation_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'graduation_year') && !Schema::hasColumn('users', 'admission_year')) {
                $table->renameColumn('graduation_year', 'admission_year');
            }
        });
    }
};
