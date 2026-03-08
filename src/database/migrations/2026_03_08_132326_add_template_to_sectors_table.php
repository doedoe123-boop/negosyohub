<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->string('template', 40)->nullable()->after('slug');
        });

        // Backfill existing sectors
        $mapping = [
            'ecommerce' => 'ecommerce',
            'real_estate' => 'real_estate',
            'paupahan' => 'rental',
            'lipat_bahay' => 'logistics',
        ];

        foreach ($mapping as $slug => $template) {
            DB::table('sectors')
                ->where('slug', $slug)
                ->update(['template' => $template]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sectors', function (Blueprint $table) {
            $table->dropColumn('template');
        });
    }
};
