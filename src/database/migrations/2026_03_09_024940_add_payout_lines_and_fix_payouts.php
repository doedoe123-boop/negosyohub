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
        Schema::table('payouts', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('reference');
        });

        Schema::create('payout_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('store_earning');
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on(config('lunar.database.table_prefix').'orders')
                ->cascadeOnDelete();

            $table->unique(['payout_id', 'order_id']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_lines');

        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn('paid_at');
        });
    }
};
