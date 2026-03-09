<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payout;
use App\Models\PayoutLine;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates store earnings from delivered orders into Payout records.
 *
 * Each delivered order's store_earning is captured as a PayoutLine.
 * Only orders that haven't already been included in a payout are processed.
 */
class PayoutService
{
    /**
     * Generate a payout for a single store covering the given period.
     *
     * Returns null if the store has no unpaid delivered orders in the period.
     */
    public function generateForStore(Store $store, Carbon $periodStart, Carbon $periodEnd): ?Payout
    {
        $orders = Order::query()
            ->forStore($store->id)
            ->delivered()
            ->whereDoesntHave('payoutLine')
            ->where('placed_at', '>=', $periodStart->startOfDay())
            ->where('placed_at', '<=', $periodEnd->endOfDay())
            ->get(['id', 'store_earning']);

        if ($orders->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($store, $orders, $periodStart, $periodEnd): Payout {
            $totalEarning = $orders->sum(fn (Order $order): int => $order->store_earning?->value ?? 0);

            $payout = Payout::create([
                'store_id' => $store->id,
                'amount' => $totalEarning / 100,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => Payout::STATUS_PENDING,
            ]);

            $lines = $orders->map(fn (Order $order): array => [
                'payout_id' => $payout->id,
                'order_id' => $order->id,
                'store_earning' => $order->store_earning?->value ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            PayoutLine::insert($lines);

            return $payout;
        });
    }

    /**
     * Generate payouts for all approved stores covering the given period.
     *
     * @return array{generated: int, skipped: int}
     */
    public function generateAll(Carbon $periodStart, Carbon $periodEnd): array
    {
        $stores = Store::query()->approved()->get();

        $generated = 0;
        $skipped = 0;

        foreach ($stores as $store) {
            $payout = $this->generateForStore($store, $periodStart, $periodEnd);

            if ($payout) {
                $generated++;
            } else {
                $skipped++;
            }
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
        ];
    }

    /**
     * Generate payouts for the previous week (Mon–Sun).
     *
     * @return array{generated: int, skipped: int}
     */
    public function generateWeekly(): array
    {
        $periodEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);
        $periodStart = $periodEnd->copy()->startOfWeek(Carbon::MONDAY);

        return $this->generateAll($periodStart, $periodEnd);
    }
}
