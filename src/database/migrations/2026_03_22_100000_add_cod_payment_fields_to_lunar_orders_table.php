<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lunar_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('lunar_orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_client_key');
            }
        });

        DB::table('lunar_orders')
            ->where('status', 'ready')
            ->update(['status' => 'shipped']);

        DB::table('lunar_orders')
            ->whereNull('payment_method')
            ->whereNotNull('payment_client_key')
            ->update(['payment_method' => 'paymongo']);

        DB::table('lunar_orders')
            ->whereNull('payment_method')
            ->whereNotNull('payment_intent_id')
            ->update(['payment_method' => 'paypal']);

        DB::table('lunar_orders')
            ->whereNull('payment_method')
            ->update(['payment_method' => 'cash_on_delivery']);

        DB::table('lunar_orders')
            ->whereIn('payment_status', ['pending', 'failed'])
            ->update(['payment_status' => 'unpaid']);

        DB::table('lunar_orders')
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->update(['payment_status' => 'paid']);

        DB::table('lunar_orders')
            ->whereNull('payment_status')
            ->update(['payment_status' => 'unpaid']);

        Schema::table('lunar_orders', function (Blueprint $table): void {
            $table->string('payment_status')->default('unpaid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        DB::table('lunar_orders')
            ->where('status', 'shipped')
            ->update(['status' => 'ready']);

        Schema::table('lunar_orders', function (Blueprint $table): void {
            $table->string('payment_status')->nullable()->default(null)->change();

            if (Schema::hasColumn('lunar_orders', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
