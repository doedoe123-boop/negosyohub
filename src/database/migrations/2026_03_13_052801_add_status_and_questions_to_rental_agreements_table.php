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
        Schema::table('rental_agreements', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('notes');
            $table->text('tenant_questions')->nullable()->after('status');
            $table->timestamp('signed_at')->nullable()->after('tenant_questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_agreements', function (Blueprint $table) {
            $table->dropColumn(['status', 'tenant_questions', 'signed_at']);
        });
    }
};
