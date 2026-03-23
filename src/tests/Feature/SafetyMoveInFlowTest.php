<?php

use App\InquiryStatus;
use App\Models\MovingBooking;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\RentalAgreement;
use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\MovingBookingStatus;
use App\PropertyStatus;
use App\SectorTemplate;
use App\UserRole;

beforeEach(function () {
    Sector::query()->create([
        'name' => 'Paupahan',
        'slug' => 'paupahan',
        'template' => SectorTemplate::Rental,
        'description' => 'Rental listings',
        'icon' => 'heroicon-o-home-modern',
        'color' => 'emerald',
        'registration_button_text' => 'Register Rental Business',
        'is_active' => true,
        'sort_order' => 3,
    ]);
});

it('returns rental safety signals and move-in journey details on the property detail endpoint', function () {
    $landlord = User::factory()->storeOwner()->create([
        'created_at' => now()->subMonths(18),
    ]);

    $store = Store::factory()->rental()->create([
        'user_id' => $landlord->id,
        'setup_completed_at' => now()->subMonths(12),
        'phone' => '09175551234',
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::Customer,
        'phone' => '09171234567',
    ]);

    $property = Property::factory()->create([
        'store_id' => $store->id,
        'status' => PropertyStatus::Active,
        'published_at' => now(),
        'address_line' => 'Tower A, Pioneer Residences',
        'city' => 'Mandaluyong City',
        'province' => 'Metro Manila',
        'images' => ['properties/demo-a.jpg', 'properties/demo-b.jpg'],
    ]);

    PropertyInquiry::factory()->create([
        'property_id' => $property->id,
        'store_id' => $store->id,
        'user_id' => $tenant->id,
        'status' => InquiryStatus::ViewingScheduled,
        'viewing_date' => now()->addDays(2),
    ]);

    $agreement = RentalAgreement::factory()->create([
        'property_id' => $property->id,
        'store_id' => $store->id,
        'tenant_user_id' => $tenant->id,
        'status' => 'signed',
        'move_in_date' => now()->addDays(10),
    ]);

    $booking = MovingBooking::factory()->create([
        'store_id' => Store::factory()->movingService()->create()->id,
        'customer_user_id' => $tenant->id,
        'rental_agreement_id' => $agreement->id,
        'status' => MovingBookingStatus::Confirmed,
    ]);

    $this->actingAs($tenant, 'sanctum')
        ->getJson(route('api.v1.properties.show', $property->slug))
        ->assertOk()
        ->assertJsonPath('data.is_verified_landlord', true)
        ->assertJsonPath('data.is_suspicious_listing', false)
        ->assertJsonPath('data.rental_journey.inquiry_status', InquiryStatus::ViewingScheduled->value)
        ->assertJsonPath('data.rental_journey.agreement_id', $agreement->id)
        ->assertJsonPath('data.rental_journey.moving_booking_id', $booking->id);
});

it('includes move-in helper context on the authenticated user rental agreements response', function () {
    $tenant = User::factory()->create(['role' => UserRole::Customer]);
    $store = Store::factory()->rental()->create([
        'phone' => '09176667777',
    ]);

    $property = Property::factory()->create([
        'store_id' => $store->id,
        'status' => PropertyStatus::Active,
        'published_at' => now(),
        'address_line' => 'Unit 4B, Maple Residences',
        'city' => 'Pasig City',
        'province' => 'Metro Manila',
    ]);

    $agreement = RentalAgreement::factory()->create([
        'property_id' => $property->id,
        'store_id' => $store->id,
        'tenant_user_id' => $tenant->id,
        'status' => 'pending',
    ]);

    $this->actingAs($tenant, 'sanctum')
        ->getJson('/api/v1/user/rental-agreements')
        ->assertOk()
        ->assertJsonPath('data.0.id', $agreement->id)
        ->assertJsonPath('data.0.property.full_address', $property->fullLocation())
        ->assertJsonPath('data.0.store.id', $store->id)
        ->assertJsonPath('data.0.store.phone', '09176667777');
});
