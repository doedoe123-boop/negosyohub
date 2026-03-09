<?php

namespace App\Console\Commands;

use App\Services\PayoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GeneratePayoutsCommand extends Command
{
    protected $signature = 'payouts:generate
        {--period-start= : Start date (Y-m-d). Defaults to last Monday.}
        {--period-end= : End date (Y-m-d). Defaults to last Sunday.}';

    protected $description = 'Generate payouts from delivered orders for all stores.';

    public function handle(PayoutService $service): int
    {
        $start = $this->option('period-start');
        $end = $this->option('period-end');

        if ($start && $end) {
            $periodStart = Carbon::parse($start);
            $periodEnd = Carbon::parse($end);

            $result = $service->generateAll($periodStart, $periodEnd);
        } else {
            $result = $service->generateWeekly();
        }

        $this->info("Payouts generated: {$result['generated']}, skipped: {$result['skipped']}");

        return self::SUCCESS;
    }
}
