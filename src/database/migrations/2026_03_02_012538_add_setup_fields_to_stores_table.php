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
            $table->string('tagline', 150)->nullable()->after('description');
            $table->string('phone', 20)->nullable()->after('logo');
            $table->string('website', 500)->nullable()->after('phone');
            $table->text('operating_hours')->nullable()->after('website');
            $table->timestamp('setup_completed_at')->nullable()->after('commission_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'phone', 'website', 'operating_hours', 'setup_completed_at']);
        });
    }
};
