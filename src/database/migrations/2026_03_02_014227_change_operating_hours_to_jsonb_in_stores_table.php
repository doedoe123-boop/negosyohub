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
        Schema::table('stores', function (Blueprint $table) {
            // Drop the old text column and re-add as jsonb
            // (safe — column was just created with no data)
            $table->dropColumn('operating_hours');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->jsonb('operating_hours')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('operating_hours');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->text('operating_hours')->nullable()->after('website');
        });
    }
};
