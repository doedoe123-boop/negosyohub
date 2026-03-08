<?php

use App\Models\Development;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Store;
use App\Models\User;
use App\PropertyStatus;
use App\StoreStatus;
use App\UserRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new \Database\Seeders\SectorSeeder)->run();

    $role = Role::firstOrCreate(['name' => 'store_owner', 'guard_name' => 'web']);
    $permissions = [
        'settings', 'settings:core', 'settings:manage-staff', 'settings:manage-attributes',
        'catalog:manage-products', 'catalog:manage-collections',
        'sales:manage-orders', 'sales:manage-customers', 'sales:manage-discounts',
    ];
    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $role->syncPermissions($permissions);
});

function realtyPath(): string
{
    return '/realty/dashboard/tk_'.config('app.realty_path_token');
}

// =========================================================
// Realty Panel Access
// =========================================================

it('allows approved real estate store owners to access the realty panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'real_estate',
    ]);

    $this->actingAs($owner)
        ->get(realtyPath())
        ->assertOk();
});

it('blocks pending real estate store owners from the realty panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Pending,
        'sector' => 'real_estate',
    ]);

    $this->actingAs($owner)
        ->get(realtyPath())
        ->assertForbidden();
});

it('blocks non-real-estate store owners from the realty panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'ecommerce',
    ]);

    $this->actingAs($owner)
        ->get(realtyPath())
        ->assertForbidden();
});

it('blocks admins from the realty panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(realtyPath())
        ->assertForbidden();
});

it('blocks customers from the realty panel', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)
        ->get(realtyPath())
        ->assertForbidden();
});

it('blocks guests from the realty panel', function () {
    $this->get(realtyPath())
        ->assertRedirect();
});

it('blocks real estate store owners from the Lunar panel', function () {
    $owner = User::factory()->storeOwner()->create();
    $owner->assignRole('store_owner');
    Store::factory()->for($owner, 'owner')->create([
        'status' => StoreStatus::Approved,
        'sector' => 'real_estate',
    ]);

    $this->actingAs($owner)
        ->get('/store/dashboard/tk_'.config('app.store_path_token'))
        ->assertForbidden();
});

// =========================================================
// Property Model
// =========================================================

it('auto-generates a slug when creating a property', function () {
    $store = Store::factory()->create([
        'status' => StoreStatus::Approved,
        'sector' => 'real_estate',
    ]);

    $property = Property::factory()->for($store)->create([
        'title' => 'Beautiful Condo in Makati',
        'slug' => null,
    ]);

    expect($property->slug)
        ->toBeString()
        ->toContain('beautiful-condo-in-makati');
});

it('scopes properties to a specific store', function () {
    $storeA = Store::factory()->create(['sector' => 'real_estate']);
    $storeB = Store::factory()->create(['sector' => 'real_estate']);

    Property::factory()->for($storeA)->count(3)->create();
    Property::factory()->for($storeB)->count(2)->create();

    expect(Property::forStore($storeA->id)->count())->toBe(3);
    expect(Property::forStore($storeB->id)->count())->toBe(2);
});

it('scopes active properties correctly', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);

    Property::factory()->for($store)->create(['status' => PropertyStatus::Active]);
    Property::factory()->for($store)->create(['status' => PropertyStatus::Active]);
    Property::factory()->for($store)->create(['status' => PropertyStatus::Draft]);
    Property::factory()->for($store)->create(['status' => PropertyStatus::Sold]);

    expect(Property::forStore($store->id)->active()->count())->toBe(2);
});

it('publishes a draft property', function () {
    $property = Property::factory()->draft()->create();

    $property->publish();

    expect($property->fresh()->status)->toBe(PropertyStatus::Active);
    expect($property->fresh()->published_at)->not->toBeNull();
});

it('formats price correctly for sale listings', function () {
    $property = Property::factory()->forSale()->create([
        'price' => 5000000,
        'price_currency' => 'PHP',
    ]);

    expect($property->formattedPrice())->toBe('PHP 5,000,000.00');
});

it('formats price correctly for rent listings', function () {
    $property = Property::factory()->forRent()->create([
        'price' => 25000,
        'price_currency' => 'PHP',
        'price_period' => 'month',
    ]);

    expect($property->formattedPrice())->toBe('PHP 25,000.00 / month');
});

it('records a view on a property', function () {
    $property = Property::factory()->create(['views_count' => 0]);

    $property->recordView();

    expect($property->fresh()->views_count)->toBe(1);
});

// =========================================================
// PropertyInquiry Model
// =========================================================

it('marks an inquiry as contacted', function () {
    $inquiry = PropertyInquiry::factory()->create();

    $inquiry->markContacted();

    $inquiry->refresh();
    expect($inquiry->status)->toBe(\App\InquiryStatus::Contacted);
    expect($inquiry->contacted_at)->not->toBeNull();
});

it('schedules a viewing for an inquiry', function () {
    $inquiry = PropertyInquiry::factory()->create();

    $viewingDate = now()->addDays(3);
    $inquiry->scheduleViewing($viewingDate);

    $inquiry->refresh();
    expect($inquiry->status)->toBe(\App\InquiryStatus::ViewingScheduled);
    expect($inquiry->viewing_date->toDateString())->toBe($viewingDate->toDateString());
});

it('scopes new inquiries', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);
    $property = Property::factory()->for($store)->create();

    PropertyInquiry::factory()->for($property)->create(['store_id' => $store->id]);
    PropertyInquiry::factory()->contacted()->for($property)->create(['store_id' => $store->id]);

    expect(PropertyInquiry::forStore($store->id)->new()->count())->toBe(1);
});

// =========================================================
// Store Relationships
// =========================================================

it('accesses properties through the store relationship', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);
    Property::factory()->for($store)->count(3)->create();

    expect($store->properties)->toHaveCount(3);
});

it('accesses property inquiries through the store relationship', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);
    $property = Property::factory()->for($store)->create();
    PropertyInquiry::factory()->for($property)->create(['store_id' => $store->id]);
    PropertyInquiry::factory()->for($property)->create(['store_id' => $store->id]);

    expect($store->propertyInquiries)->toHaveCount(2);
});

// =========================================================
// Development Model
// =========================================================

it('auto-generates a slug when creating a development', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);

    $development = Development::factory()->for($store)->create([
        'name' => 'Sunrise Towers Manila',
        'slug' => null,
    ]);

    expect($development->slug)
        ->toBeString()
        ->toContain('sunrise-towers-manila');
});

it('scopes developments to a specific store', function () {
    $storeA = Store::factory()->create(['sector' => 'real_estate']);
    $storeB = Store::factory()->create(['sector' => 'real_estate']);

    Development::factory()->for($storeA)->count(3)->create();
    Development::factory()->for($storeB)->count(2)->create();

    expect(Development::forStore($storeA->id)->count())->toBe(3);
    expect(Development::forStore($storeB->id)->count())->toBe(2);
});

it('scopes active developments correctly', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);

    Development::factory()->for($store)->create(['status' => 'active']);
    Development::factory()->for($store)->create(['status' => 'active']);
    Development::factory()->for($store)->create(['status' => 'draft']);
    Development::factory()->for($store)->create(['status' => 'sold_out']);

    expect(Development::forStore($store->id)->active()->count())->toBe(2);
});

it('formats price range correctly', function () {
    $development = Development::factory()->create([
        'price_range_min' => 2500000,
        'price_range_max' => 8500000,
    ]);

    expect($development->priceRange())->toBe('₱2,500,000 – ₱8,500,000');
});

it('shows price on request when no prices set', function () {
    $development = Development::factory()->create([
        'price_range_min' => null,
        'price_range_max' => null,
    ]);

    expect($development->priceRange())->toBe('Price on request');
});

it('formats full location correctly', function () {
    $development = Development::factory()->create([
        'address_line' => '123 Rizal Ave',
        'barangay' => 'Poblacion',
        'city' => 'Makati',
        'province' => 'Metro Manila',
    ]);

    expect($development->fullLocation())->toBe('123 Rizal Ave, Poblacion, Makati, Metro Manila');
});

it('syncs available units from property listings', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);
    $development = Development::factory()->for($store)->create(['available_units' => 0]);

    Property::factory()->for($store)->create([
        'development_id' => $development->id,
        'status' => PropertyStatus::Active,
    ]);
    Property::factory()->for($store)->create([
        'development_id' => $development->id,
        'status' => PropertyStatus::Active,
    ]);
    Property::factory()->for($store)->create([
        'development_id' => $development->id,
        'status' => PropertyStatus::Sold,
    ]);

    $development->syncAvailableUnits();

    expect($development->fresh()->available_units)->toBe(2);
});

it('accesses developments through the store relationship', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);
    Development::factory()->for($store)->count(4)->create();

    expect($store->developments)->toHaveCount(4);
});

it('links properties to a development', function () {
    $store = Store::factory()->create(['sector' => 'real_estate']);
    $development = Development::factory()->for($store)->create();

    $property = Property::factory()->for($store)->create([
        'development_id' => $development->id,
        'unit_number' => 'Unit 5A',
        'unit_floor' => '5th Floor',
    ]);

    expect($property->development->id)->toBe($development->id);
    expect($development->properties)->toHaveCount(1);
    expect($property->unit_number)->toBe('Unit 5A');
    expect($property->unit_floor)->toBe('5th Floor');
});
