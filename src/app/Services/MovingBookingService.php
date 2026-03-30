<?php

namespace App\Services;

use App\Models\MovingAddOn;
use App\Models\MovingBooking;
use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\MovingBookingStatus;
use App\PaymentStatus;
use App\SectorTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for creating and managing Lipat Bahay moving bookings.
 */
class MovingBookingService
{
    /**
     * Browse approved moving company stores.
     *
     * @param  array{search?: string, city?: string, province?: string, per_page?: int}  $params
     */
    public function browseMovers(array $params = []): LengthAwarePaginator
    {
        $logisticsSlugs = Sector::query()
            ->where('template', SectorTemplate::Logistics)
            ->pluck('slug')
            ->toArray();

        $query = Store::query()
            ->whereIn('sector', $logisticsSlugs)
            ->where('status', 'approved');

        if (! empty($params['search'])) {
            $query->where('name', 'like', '%'.$params['search'].'%');
        }

        if (! empty($params['city'])) {
            $query->where('address->city', $params['city']);
        }

        if (! empty($params['province'])) {
            $query->where('address->province', $params['province']);
        }

        $perPage = min((int) ($params['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /**
     * Create a new moving booking.
     *
     * @param  array{store_id: int, rental_agreement_id?: int, pickup_address: string, delivery_address: string, pickup_city: string, delivery_city: string, scheduled_at: string, contact_name: string, contact_phone: string, notes?: string, add_on_ids?: array<int>}  $data
     */
    public function createBooking(User $customer, array $data): MovingBooking
    {
        $logisticsSlugs = Sector::query()
            ->where('template', SectorTemplate::Logistics)
            ->pluck('slug')
            ->toArray();

        $store = Store::query()
            ->where('id', $data['store_id'])
            ->whereIn('sector', $logisticsSlugs)
            ->firstOrFail();

        $pricing = $this->calculatePricing($store, $data['add_on_ids'] ?? []);
        $addOns = $pricing['add_ons'];
        $addOnsTotal = $pricing['add_ons_total'];
        $basePrice = $pricing['base_price'];
        $totalPrice = $basePrice + $addOnsTotal;

        $booking = MovingBooking::create([
            'store_id' => $store->id,
            'customer_user_id' => $customer->id,
            'rental_agreement_id' => $data['rental_agreement_id'] ?? null,
            'status' => MovingBookingStatus::Pending,
            'pickup_address' => $data['pickup_address'],
            'delivery_address' => $data['delivery_address'],
            'pickup_city' => $data['pickup_city'],
            'delivery_city' => $data['delivery_city'],
            'scheduled_at' => $data['scheduled_at'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'notes' => $data['notes'] ?? null,
            'base_price' => $basePrice,
            'add_ons_total' => $addOnsTotal,
            'total_price' => $totalPrice,
            'payment_status' => PaymentStatus::Pending,
        ]);

        if ($addOns->isNotEmpty()) {
            $pivotData = $addOns->mapWithKeys(
                fn ($addOn) => [$addOn->id => ['price' => $addOn->price]]
            )->toArray();

            $booking->addOns()->attach($pivotData);
        }

        return $booking->load(['addOns', 'store']);
    }

    /**
     * Transition a booking to a new status (called by the moving company owner).
     */
    public function updateStatus(MovingBooking $booking, MovingBookingStatus $status): MovingBooking
    {
        if (! $booking->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Moving bookings cannot move from %s to %s.',
                    strtolower($booking->status->label()),
                    strtolower($status->label())
                ),
            ]);
        }

        $booking->update(['status' => $status]);

        return $booking->fresh('addOns');
    }

    /**
     * @param  list<int>|array<int>  $addOnIds
     * @return array{base_price: int, add_ons_total: int, add_ons: Collection<int, MovingAddOn>}
     */
    public function calculatePricing(Store $store, array $addOnIds = []): array
    {
        $basePrice = (int) ($store->moving_base_price ?? 0);

        if ($basePrice <= 0) {
            throw ValidationException::withMessages([
                'store_id' => 'This moving provider is not accepting bookings until a base service rate is configured.',
            ]);
        }

        $normalizedAddOnIds = array_values(array_unique(array_map('intval', $addOnIds)));

        $addOns = $normalizedAddOnIds === []
            ? collect()
            : $store->movingAddOns()
                ->whereIn('id', $normalizedAddOnIds)
                ->where('is_active', true)
                ->get();

        if (count($normalizedAddOnIds) !== $addOns->count()) {
            throw ValidationException::withMessages([
                'add_on_ids' => 'One or more selected add-on services are no longer available for this provider.',
            ]);
        }

        return [
            'base_price' => $basePrice,
            'add_ons_total' => (int) $addOns->sum('price'),
            'add_ons' => $addOns,
        ];
    }
}
