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
        Schema::create('moving_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rental_agreement_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status')->default('pending');

            // Move details
            $table->string('pickup_address');
            $table->string('delivery_address');
            $table->string('pickup_city');
            $table->string('delivery_city');
            $table->dateTime('scheduled_at');

            // Contact
            $table->string('contact_name');
            $table->string('contact_phone', 30);
            $table->text('notes')->nullable();

            // Pricing (centavos)
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('add_ons_total')->default(0);
            $table->unsignedBigInteger('total_price');

            // Payment
            $table->string('payment_status')->default('pending');
            $table->string('paymongo_payment_intent_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot: which add-ons were selected per booking
        Schema::create('moving_booking_add_on', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moving_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moving_add_on_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price'); // snapshot of price at booking time
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moving_booking_add_on');
        Schema::dropIfExists('moving_bookings');
    }
};
