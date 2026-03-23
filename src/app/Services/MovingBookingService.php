<?php

namespace App\Services;

use App\Models\MovingBooking;
use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\MovingBookingStatus;
use App\PaymentStatus;
use App\SectorTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * @param  array{store_id: int, rental_agreement_id?: int, pickup_address: string, delivery_address: string, pickup_city: string, delivery_city: string, scheduled_at: string, contact_name: string, contact_phone: string, notes?: string, add_on_ids?: array<int>, base_price?: int}  $data
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

        // Gather selected add-ons from this store
        $addOns = collect();
        $addOnsTotal = 0;

        if (! empty($data['add_on_ids'])) {
            $addOns = $store->movingAddOns()
                ->whereIn('id', $data['add_on_ids'])
                ->where('is_active', true)
                ->get();

            $addOnsTotal = $addOns->sum('price');
        }

        $basePrice = (int) ($data['base_price'] ?? 0);
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
        $booking->update(['status' => $status]);

        return $booking->fresh('addOns');
    }
}
