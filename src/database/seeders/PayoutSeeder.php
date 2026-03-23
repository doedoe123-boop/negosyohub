<?php

namespace Database\Seeders;

use App\Models\Payout;
use App\Models\Store;
use Illuminate\Database\Seeder;

class PayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::query()->where('status', 'approved')->get();

        foreach ($stores as $store) {
            $periods = [
                [
                    'period_start' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                    'period_end' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                    'amount' => 18_450.75,
                    'status' => Payout::STATUS_PAID,
                    'reference' => 'PAYOUT-'.str($store->slug)->upper()->replace('-', '').'-01',
                    'paid_at' => now()->subWeeks(2),
                ],
                [
                    'period_start' => now()->startOfMonth()->toDateString(),
                    'period_end' => now()->endOfMonth()->toDateString(),
                    'amount' => 24_980.50,
                    'status' => Payout::STATUS_PENDING,
                    'reference' => null,
                    'paid_at' => null,
                ],
            ];

            foreach ($periods as $period) {
                Payout::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'period_start' => $period['period_start'],
                        'period_end' => $period['period_end'],
                    ],
                    $period
                );
            }
        }
    }
}
