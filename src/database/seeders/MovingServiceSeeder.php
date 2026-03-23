<?php

namespace Database\Seeders;

use App\Models\MovingAddOn;
use App\Models\MovingBooking;
use App\Models\MovingReview;
use App\Models\RentalAgreement;
use App\Models\Store;
use App\Models\User;
use App\MovingBookingStatus;
use App\PaymentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class MovingServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::query()->where('email', 'customer@negosyohub.test')->firstOrFail();
        $tenant = User::query()->where('email', 'tenant@negosyohub.test')->firstOrFail();
        $rentalAgreement = RentalAgreement::query()->latest('signed_at')->first();

        foreach ($this->catalog() as $storeSlug => $data) {
            $store = Store::query()->where('slug', $storeSlug)->first();

            if (! $store) {
                $this->command?->warn("MovingServiceSeeder: store [{$storeSlug}] not found — skipping.");

                continue;
            }

            $addOns = collect();

            foreach ($data['add_ons'] as $addOn) {
                $record = MovingAddOn::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'name' => $addOn['name'],
                    ],
                    [
                        'description' => $addOn['description'],
                        'price' => $addOn['price'],
                        'is_active' => true,
                    ]
                );

                $addOns->push($record);
            }

            $this->seedPendingBooking($store, $customer, $addOns, $data);
            $completedBooking = $this->seedCompletedBooking($store, $tenant, $addOns, $data, $rentalAgreement);

            MovingReview::query()->updateOrCreate(
                ['moving_booking_id' => $completedBooking->id],
                [
                    'store_id' => $store->id,
                    'customer_user_id' => $tenant->id,
                    'rating' => 5,
                    'comment' => $data['review'],
                    'is_published' => true,
                ]
            );
        }
    }

    /**
     * @return array<string, array{pickup_city: string, delivery_city: string, base_price: int, add_ons: list<array{name: string, description: string, price: int}>, review: string}>
     */
    private function catalog(): array
    {
        return [
            'bayanihan-movers' => [
                'pickup_city' => 'Makati City',
                'delivery_city' => 'Taguig City',
                'base_price' => 12_500_00,
                'add_ons' => [
                    ['name' => 'Packing Crew', 'description' => 'Two trained packers with boxes and wrapping materials.', 'price' => 2_500_00],
                    ['name' => 'Appliance Handling', 'description' => 'Special handling for refrigerators, washers, and aircons.', 'price' => 1_800_00],
                    ['name' => 'Assembly Help', 'description' => 'Disassembly and reassembly of bed frames and cabinets.', 'price' => 1_500_00],
                ],
                'review' => 'The team arrived early, packed everything carefully, and finished our condo move in one afternoon.',
            ],
            'lipat-express-ph' => [
                'pickup_city' => 'Quezon City',
                'delivery_city' => 'Pasig City',
                'base_price' => 9_800_00,
                'add_ons' => [
                    ['name' => 'Extra Helpers', 'description' => 'Adds two more movers for faster loading and unloading.', 'price' => 1_600_00],
                    ['name' => 'Fragile Item Crating', 'description' => 'Protective crating for mirrors, TVs, and glass tables.', 'price' => 2_100_00],
                    ['name' => 'Weekend Slot', 'description' => 'Preferred Saturday or Sunday move schedule.', 'price' => 1_200_00],
                ],
                'review' => 'Great communication and no hidden charges. They handled our fragile items well.',
            ],
            'vismin-relocation-co' => [
                'pickup_city' => 'Cebu City',
                'delivery_city' => 'Mandaue City',
                'base_price' => 8_900_00,
                'add_ons' => [
                    ['name' => 'Packing Materials Kit', 'description' => 'Boxes, bubble wrap, tape, and labels for a two-bedroom move.', 'price' => 1_400_00],
                    ['name' => 'Furniture Protection', 'description' => 'Blankets and corner guards for large furniture pieces.', 'price' => 1_100_00],
                    ['name' => 'Post-Move Cleanup', 'description' => 'Light cleanup of the vacated unit after loading.', 'price' => 900_00],
                ],
                'review' => 'Very smooth relocation from our old unit to the new condo. The crew was respectful and efficient.',
            ],
        ];
    }

    private function seedPendingBooking(Store $store, User $customer, Collection $addOns, array $data): void
    {
        $selectedAddOns = $addOns->take(2);
        $addOnsTotal = (int) $selectedAddOns->sum('price');

        $booking = MovingBooking::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'customer_user_id' => $customer->id,
                'notes' => 'Demo pending household move booking.',
            ],
            [
                'rental_agreement_id' => null,
                'status' => MovingBookingStatus::Confirmed,
                'pickup_address' => 'Unit 12B, Legazpi Residences, 117 Rufino St.',
                'delivery_address' => 'Tower 3, Uptown Parksuites, 9th Avenue',
                'pickup_city' => $data['pickup_city'],
                'delivery_city' => $data['delivery_city'],
                'scheduled_at' => now()->addDays(6)->setTime(9, 0),
                'contact_name' => $customer->name,
                'contact_phone' => $customer->phone ?? '09171234567',
                'base_price' => $data['base_price'],
                'add_ons_total' => $addOnsTotal,
                'total_price' => $data['base_price'] + $addOnsTotal,
                'payment_status' => PaymentStatus::Pending,
                'paymongo_payment_intent_id' => null,
            ]
        );

        $booking->addOns()->sync(
            $selectedAddOns
                ->mapWithKeys(fn (MovingAddOn $addOn): array => [$addOn->id => ['price' => $addOn->price]])
                ->all()
        );
    }

    private function seedCompletedBooking(
        Store $store,
        User $tenant,
        Collection $addOns,
        array $data,
        ?RentalAgreement $rentalAgreement
    ): MovingBooking {
        $selectedAddOns = $addOns->take(1);
        $addOnsTotal = (int) $selectedAddOns->sum('price');

        $booking = MovingBooking::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'customer_user_id' => $tenant->id,
                'notes' => 'Demo completed relocation booking linked to rental move-in.',
            ],
            [
                'rental_agreement_id' => $rentalAgreement?->id,
                'status' => MovingBookingStatus::Completed,
                'pickup_address' => '45 Scout Ybardolaza St., Quezon City',
                'delivery_address' => $rentalAgreement?->property?->fullLocation() ?? 'Azure Heights Road, Molino IV, Bacoor, Cavite',
                'pickup_city' => $data['pickup_city'],
                'delivery_city' => $rentalAgreement?->property?->city ?? $data['delivery_city'],
                'scheduled_at' => now()->subDays(4)->setTime(8, 30),
                'contact_name' => $tenant->name,
                'contact_phone' => $tenant->phone ?? '09179876543',
                'base_price' => $data['base_price'],
                'add_ons_total' => $addOnsTotal,
                'total_price' => $data['base_price'] + $addOnsTotal,
                'payment_status' => PaymentStatus::Paid,
                'paymongo_payment_intent_id' => 'pi_demo_'.str($store->slug)->replace('-', '_'),
            ]
        );

        $booking->addOns()->sync(
            $selectedAddOns
                ->mapWithKeys(fn (MovingAddOn $addOn): array => [$addOn->id => ['price' => $addOn->price]])
                ->all()
        );

        return $booking;
    }
}
