<?php

namespace App\Services;

use App\InquiryStatus;
use App\Models\OpenHouse;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\PropertyStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only property browsing service for the customer storefront.
 *
 * Only active, published properties are ever surfaced publicly.
 *
 * @see /skills/store-management.md
 */
class PropertyService
{
    /**
     * Browse active published properties with optional filters.
     *
     * @param  array{search?: string, type?: string, listing_type?: string, min_price?: numeric, max_price?: numeric, bedrooms?: int, city?: string, featured?: bool, per_page?: int}  $params
     */
    public function browse(array $params = []): LengthAwarePaginator
    {
        $query = Property::query()
            ->with(['store:id,name,slug,logo', 'media'])
            ->where('status', PropertyStatus::Active)
            ->whereNotNull('published_at')
            ->latest('published_at');

        if (! empty($params['search'])) {
            $search = $params['search'];
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $like) {
                $q->where('title', $like, "%{$search}%")
                    ->orWhere('city', $like, "%{$search}%")
                    ->orWhere('address_line', $like, "%{$search}%");
            });
        }

        if (! empty($params['type'])) {
            $query->where('property_type', $params['type']);
        }

        if (! empty($params['listing_type'])) {
            $query->where('listing_type', $params['listing_type']);
        }

        if (isset($params['min_price']) && $params['min_price'] !== '') {
            $query->where('price', '>=', $params['min_price']);
        }

        if (isset($params['max_price']) && $params['max_price'] !== '') {
            $query->where('price', '<=', $params['max_price']);
        }

        if (! empty($params['bedrooms'])) {
            $query->where('bedrooms', '>=', (int) $params['bedrooms']);
        }

        if (! empty($params['city'])) {
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where('city', $like, "%{$params['city']}%");
        }

        if (! empty($params['featured'])) {
            $query->where('is_featured', true);
        }

        return $query->paginate($params['per_page'] ?? 12);
    }

    /**
     * Return the N most recently published featured (or just latest) active properties.
     *
     * @return Collection<int, Property>
     */
    public function featured(int $limit = 4): Collection
    {
        return Property::query()
            ->with('media')
            ->where('status', PropertyStatus::Active)
            ->whereNotNull('published_at')
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Find a single active property by slug, increment its view count, and return it.
     */
    public function findBySlugOrFail(string $slug): Property
    {
        $property = Property::query()
            ->with([
                'store:id,user_id,name,slug,logo,agent_bio,agent_photo,phone',
                'store.owner:id,name',
                'development:id,name,slug,developer_name',
            ])
            ->where('slug', $slug)
            ->whereIn('status', [PropertyStatus::Active, PropertyStatus::UnderOffer, PropertyStatus::Rented])
            ->firstOrFail();

        $property->increment('views_count');

        return $property->fresh(['store', 'store.owner', 'development', 'media']);
    }

    /**
     * Browse active published properties scoped to a single approved store.
     *
     * @param  array<string, mixed>  $params
     */
    public function browseForStore(Store $store, array $params = []): LengthAwarePaginator
    {
        $query = Property::query()
            ->with('media')
            ->where('store_id', $store->id)
            ->where('status', PropertyStatus::Active)
            ->whereNotNull('published_at')
            ->latest('published_at');

        if (! empty($params['search'])) {
            $search = $params['search'];
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $like) {
                $q->where('title', $like, "%{$search}%")
                    ->orWhere('city', $like, "%{$search}%");
            });
        }

        if (! empty($params['type'])) {
            $query->where('property_type', $params['type']);
        }

        if (! empty($params['listing_type'])) {
            $query->where('listing_type', $params['listing_type']);
        }

        if (isset($params['min_price']) && $params['min_price'] !== '') {
            $query->where('price', '>=', $params['min_price']);
        }

        if (isset($params['max_price']) && $params['max_price'] !== '') {
            $query->where('price', '<=', $params['max_price']);
        }

        if (! empty($params['bedrooms'])) {
            $query->where('bedrooms', '>=', (int) $params['bedrooms']);
        }

        return $query->paginate($params['per_page'] ?? 12);
    }

    /**
     * Create a new inquiry for a property from validated input.
     *
     * The observer on PropertyInquiry will fire notifications to the agent.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitInquiry(Property $property, array $data): PropertyInquiry
    {
        abort_if($property->status !== PropertyStatus::Active, 404);

        return PropertyInquiry::create([
            'property_id' => $property->id,
            'store_id' => $property->store_id,
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'source' => $data['source'] ?? 'website',
            'status' => InquiryStatus::New,
        ]);
    }

    /**
     * Submit a quick inquiry from an authenticated user.
     *
     * Pulls the user's name, email, and phone automatically.
     * Generates a friendly default message when none is provided.
     */
    public function submitQuickInquiry(Property $property, User $user, ?string $message = null): PropertyInquiry
    {
        abort_if($property->status !== PropertyStatus::Active, 404);

        $message ??= $this->generateDefaultMessage($property, $user);

        return PropertyInquiry::create([
            'property_id' => $property->id,
            'store_id' => $property->store_id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'message' => $message,
            'source' => 'website',
            'status' => InquiryStatus::New,
        ]);
    }

    /**
     * Build a friendly default inquiry message.
     */
    private function generateDefaultMessage(Property $property, User $user): string
    {
        $recipientLabel = $property->store?->isPaupahan() ? 'landlord' : 'agent';
        $contactName = $property->store?->agent_name ?? $recipientLabel;

        return "Hi {$contactName}, I'm {$user->name} and I'm interested in your listing \"{$property->title}\". I'd love to schedule a viewing or learn more about this property. Looking forward to hearing from you!";
    }

    /**
     * Return upcoming published open house events for a property.
     *
     * @return Collection<int, OpenHouse>
     */
    public function upcomingOpenHouses(Property $property): Collection
    {
        return OpenHouse::query()
            ->where('property_id', $property->id)
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();
    }
}
