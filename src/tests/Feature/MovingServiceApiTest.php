<?php

use App\Models\MovingAddOn;
use App\Models\MovingBooking;
use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\SectorTemplate;

it('lists approved movers using address city filters', function () {
    $sector = Sector::query()->create([
        'name' => 'Lipat Bahay',
        'slug' => 'lipat_bahay',
        'template' => SectorTemplate::Logistics,
        'description' => 'Moving services',
        'icon' => 'heroicon-o-truck',
        'color' => 'violet',
        'registration_button_text' => 'Register as Moving Service',
        'is_active' => true,
        'sort_order' => 4,
    ]);

    Store::factory()->movingService()->create([
        'name' => 'Bayanihan Movers',
        'sector' => $sector->slug,
        'address' => [
            'line1' => '123 Chino Roces Ave',
            'barangay' => 'Pio del Pilar',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'postal_code' => '1230',
        ],
    ]);

    Store::factory()->movingService()->create([
        'name' => 'VisMin Relocation',
        'sector' => $sector->slug,
        'address' => [
            'line1' => '1 Gov. Cuenco Ave',
            'barangay' => 'Kasambagan',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'postal_code' => '6000',
        ],
    ]);

    $response = $this->getJson('/api/v1/movers?city=Makati%20City');

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Bayanihan Movers')
        ->assertJsonCount(1, 'data');
});

it('creates a moving booking for a customer using the moving booking API', function () {
    $customer = User::factory()->create();

    $sector = Sector::query()->create([
        'name' => 'Lipat Bahay',
        'slug' => 'lipat_bahay',
        'template' => SectorTemplate::Logistics,
        'description' => 'Moving services',
        'icon' => 'heroicon-o-truck',
        'color' => 'violet',
        'registration_button_text' => 'Register as Moving Service',
        'is_active' => true,
        'sort_order' => 4,
    ]);

    $store = Store::factory()->movingService()->create([
        'sector' => $sector->slug,
    ]);

    $addOn = MovingAddOn::factory()->create([
        'store_id' => $store->id,
        'is_active' => true,
        'price' => 150000,
    ]);

    $response = $this->actingAs($customer, 'sanctum')->postJson('/api/v1/moving-bookings', [
        'store_id' => $store->id,
        'pickup_address' => 'Unit 12B, Legazpi Residences',
        'delivery_address' => 'Tower 3, Uptown Parksuites',
        'pickup_city' => 'Makati City',
        'delivery_city' => 'Taguig City',
        'scheduled_at' => now()->addDays(3)->toIso8601String(),
        'contact_name' => 'Demo Customer',
        'contact_phone' => '09171234567',
        'base_price' => 1_250_000,
        'add_on_ids' => [$addOn->id],
    ]);

    $response->assertCreated()
        ->assertJsonPath('store_id', $store->id)
        ->assertJsonPath('customer_user_id', $customer->id)
        ->assertJsonPath('add_ons_total', 150000)
        ->assertJsonPath('total_price', 1400000);

    expect(MovingBooking::query()->count())->toBe(1);
});
