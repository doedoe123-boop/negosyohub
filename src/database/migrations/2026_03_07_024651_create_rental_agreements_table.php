<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tenant_name');
            $table->string('tenant_email');
            $table->string('tenant_phone', 30)->nullable();

            // Stored as integer cents to avoid floating-point rounding
            $table->unsignedBigInteger('monthly_rent');
            $table->unsignedBigInteger('security_deposit')->nullable();

            $table->date('move_in_date');
            $table->unsignedSmallInteger('lease_term_months')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
};
