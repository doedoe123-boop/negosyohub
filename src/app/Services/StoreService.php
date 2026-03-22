<?php

namespace App\Services;

use App\Mail\StoreRejected;
use App\Models\Store;
use App\Models\User;
use App\Services\Webhooks\WebhookEventDispatcher;
use App\StoreStatus;
use App\UserRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Handles store registration, approval, and management.
 *
 * @see /skills/store-management.md
 * @see /agent/store-agent.md
 */
class StoreService
{
    public function __construct(
        private WebhookEventDispatcher $webhookEventDispatcher
    ) {}

    /**
     * Register a new store for a user.
     */
    public function register(User $user, array $data): Store
    {
        $user->update(['role' => UserRole::StoreOwner]);

        return Store::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? null,
            'address' => $data['address'] ?? null,
            'sector' => $data['sector'] ?? null,
            'commission_rate' => $data['commission_rate'] ?? 15.00,
            'status' => StoreStatus::Pending,
        ]);
    }

    /**
     * Approve a pending store.
     *
     * Generates a unique login token so the owner
     * receives a non-guessable login URL.
     */
    public function approve(Store $store): Store
    {
        $store->update(['status' => StoreStatus::Approved]);
        $store->generateLoginToken();
        $store = $store->refresh();

        $this->webhookEventDispatcher->dispatch('store.approved', [
            'event' => 'store.approved',
            'occurred_at' => now()->toIso8601String(),
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'sector' => $store->sector,
                'status' => $store->status->value,
            ],
        ], $store);

        return $store;
    }

    /**
     * Reject a pending store with a reason.
     *
     * Sends a rejection email to the store owner.
     */
    public function reject(Store $store, string $reason): Store
    {
        $store->update([
            'status' => StoreStatus::Rejected,
            'suspension_reason' => $reason,
        ]);

        Mail::to($store->owner->email)->queue(
            new StoreRejected($store, $reason)
        );

        return $store->refresh();
    }

    /**
     * Suspend an active store with a reason.
     */
    public function suspend(Store $store, string $reason): Store
    {
        $store->update([
            'status' => StoreStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        return $store->refresh();
    }

    /**
     * Reinstate a suspended store.
     *
     * Generates a fresh login token for security (old links
     * from before suspension are invalidated).
     */
    public function reinstate(Store $store): Store
    {
        $store->update([
            'status' => StoreStatus::Approved,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
        $store->generateLoginToken();

        return $store->refresh();
    }

    /**
     * Return a paginated list of approved stores for the customer storefront.
     *
     * Supports optional filtering by sector, city, and a name search term.
     *
     * @param  array{sector?: string|null, city?: string|null, search?: string|null, per_page?: int}  $filters
     * @return LengthAwarePaginator
     */
    public function browseApproved(array $filters = [])
    {
        $query = Store::query()->where('status', StoreStatus::Approved);

        if (! empty($filters['search']) && $this->usesScout()) {
            $ids = Store::search($filters['search'])
                ->take(250)
                ->get()
                ->pluck('id')
                ->all();

            $query->whereIn('id', empty($ids) ? [0] : $ids);
        }

        if (! empty($filters['sector'])) {
            $query->where('sector', $filters['sector']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['search']) && ! $this->usesScout()) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where('name', $like, '%'.$filters['search'].'%');
        }

        if (! empty($filters['collection_id'])) {
            $collectionId = (int) $filters['collection_id'];
            $query->whereHas('collections', fn ($q) => $q->where('lunar_collections.id', $collectionId));
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Find a single approved store, or abort with 404.
     */
    public function findApprovedOrFail(Store $store): Store
    {
        abort_if($store->status !== StoreStatus::Approved, 404);

        return $store;
    }

    private function usesScout(): bool
    {
        return config('scout.driver') !== 'null' && method_exists(Store::class, 'search');
    }
}
