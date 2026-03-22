<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('lunar_orders')->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('external_reference')->nullable()->index();
            $table->string('delivery_status')->default('pending');
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('tracking_url')->nullable();
            $table->text('pickup_address')->nullable();
            $table->text('dropoff_address')->nullable();
            $table->json('booking_payload')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
