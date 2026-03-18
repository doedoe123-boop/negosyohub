<?php

namespace App\Jobs;

use App\Mail\SavedSearchResultsMail;
use App\Models\SavedSearch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifySavedSearchesJob implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function handle(): void
    {
        $this->processFrequency('daily');
        $this->processFrequency('weekly');
    }

    private function processFrequency(string $frequency): void
    {
        $threshold = $frequency === 'daily'
            ? now()->subDay()
            : now()->subWeek();

        SavedSearch::query()
            ->active()
            ->where('notify_frequency', $frequency)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_notified_at')
                    ->orWhere('last_notified_at', '<', $threshold);
            })
            ->with('user')
            ->chunk(100, function ($searches) {
                foreach ($searches as $search) {
                    $baseQuery = $search->toPropertyQuery()
                        ->whereNotNull('published_at');

                    if ($search->last_notified_at) {
                        $baseQuery->where('published_at', '>', $search->last_notified_at);
                    }

                    $properties = $baseQuery
                        ->with('media')
                        ->latest('published_at')
                        ->limit(10)
                        ->get();

                    if ($properties->isEmpty()) {
                        continue;
                    }

                    Mail::to($search->user->email)
                        ->queue(new SavedSearchResultsMail($search, $properties));

                    $search->markNotified();
                }
            });
    }
}
