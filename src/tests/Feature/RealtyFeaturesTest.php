<?php

use App\ListingType;
use App\Models\OpenHouse;
use App\Models\OpenHouseRsvp;
use App\Models\Property;
use App\Models\PropertyAnalytic;
use App\Models\SavedSearch;
use App\Models\Store;
use App\Models\Testimonial;
use App\Models\User;
use App\PropertyStatus;
use App\PropertyType;

beforeEach(function () {
    $this->store = Store::factory()->create([
        'sector' => 'real_estate',
    ]);
});

// =========================================================
// Open House Model
// =========================================================

it('creates an open house for a property', function () {
    $property = Property::factory()->for($this->store)->create();

    $openHouse = OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'title' => 'Weekend Open House',
        'event_date' => now()->addDays(5),
        'start_time' => '10:00',
        'end_time' => '16:00',
    ]);

    expect($openHouse->property->id)->toBe($property->id);
    expect($openHouse->store->id)->toBe($this->store->id);
    expect($openHouse->title)->toBe('Weekend Open House');
});

it('scopes upcoming open houses', function () {
    $property = Property::factory()->for($this->store)->create();

    OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'event_date' => now()->addDays(3),
        'status' => 'scheduled',
    ]);
    OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'event_date' => now()->subDays(1),
        'status' => 'completed',
    ]);

    expect(OpenHouse::forStore($this->store->id)->upcoming()->count())->toBe(1);
});

it('cancels an open house', function () {
    $property = Property::factory()->for($this->store)->create();
    $openHouse = OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
    ]);

    $openHouse->cancel();

    expect($openHouse->fresh()->status)->toBe('cancelled');
});

it('completes an open house', function () {
    $property = Property::factory()->for($this->store)->create();
    $openHouse = OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
    ]);

    $openHouse->complete();

    expect($openHouse->fresh()->status)->toBe('completed');
});

it('formats time range correctly', function () {
    $property = Property::factory()->for($this->store)->create();
    $openHouse = OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'start_time' => '10:00:00',
        'end_time' => '16:00:00',
    ]);

    expect($openHouse->timeRange())->toBe('10:00 – 16:00');
});

it('tracks RSVP capacity', function () {
    $property = Property::factory()->for($this->store)->create();
    $openHouse = OpenHouse::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'max_attendees' => 2,
    ]);

    OpenHouseRsvp::factory()->create(['open_house_id' => $openHouse->id, 'status' => 'confirmed']);
    expect($openHouse->hasCapacity())->toBeTrue();

    OpenHouseRsvp::factory()->create(['open_house_id' => $openHouse->id, 'status' => 'confirmed']);
    expect($openHouse->hasCapacity())->toBeFalse();
});

// =========================================================
// Open House RSVP Model
// =========================================================

it('marks an RSVP as attended', function () {
    $rsvp = OpenHouseRsvp::factory()->create();

    $rsvp->markAttended();

    expect($rsvp->fresh()->status)->toBe('attended');
});

it('marks an RSVP as no-show', function () {
    $rsvp = OpenHouseRsvp::factory()->create();

    $rsvp->markNoShow();

    expect($rsvp->fresh()->status)->toBe('no_show');
});

it('cancels an RSVP', function () {
    $rsvp = OpenHouseRsvp::factory()->create();

    $rsvp->cancel();

    expect($rsvp->fresh()->status)->toBe('cancelled');
});

// =========================================================
// Testimonial Model
// =========================================================

it('creates a testimonial for a store', function () {
    $testimonial = Testimonial::factory()->create([
        'store_id' => $this->store->id,
        'client_name' => 'Juan Dela Cruz',
        'rating' => 5,
        'content' => 'Excellent service!',
    ]);

    expect($testimonial->store->id)->toBe($this->store->id);
    expect($testimonial->rating)->toBe(5);
});

it('scopes published testimonials', function () {
    Testimonial::factory()->published()->create(['store_id' => $this->store->id]);
    Testimonial::factory()->published()->create(['store_id' => $this->store->id]);
    Testimonial::factory()->unpublished()->create(['store_id' => $this->store->id]);

    expect(Testimonial::forStore($this->store->id)->published()->count())->toBe(2);
});

it('scopes featured testimonials', function () {
    Testimonial::factory()->featured()->create(['store_id' => $this->store->id]);
    Testimonial::factory()->published()->create(['store_id' => $this->store->id, 'is_featured' => false]);

    expect(Testimonial::forStore($this->store->id)->featured()->count())->toBe(1);
});

it('publishes a testimonial', function () {
    $testimonial = Testimonial::factory()->unpublished()->create(['store_id' => $this->store->id]);

    $testimonial->publish();

    expect($testimonial->fresh()->is_published)->toBeTrue();
    expect($testimonial->fresh()->published_at)->not->toBeNull();
});

it('unpublishes a testimonial', function () {
    $testimonial = Testimonial::factory()->published()->create(['store_id' => $this->store->id]);

    $testimonial->unpublish();

    expect($testimonial->fresh()->is_published)->toBeFalse();
});

it('generates star rating string', function () {
    $testimonial = Testimonial::factory()->create([
        'store_id' => $this->store->id,
        'rating' => 4,
    ]);

    expect($testimonial->starRating())->toBe('★★★★☆');
});

it('links a testimonial to a specific property', function () {
    $property = Property::factory()->for($this->store)->create();
    $testimonial = Testimonial::factory()->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
    ]);

    expect($testimonial->property->id)->toBe($property->id);
});

// =========================================================
// Saved Search Model
// =========================================================

it('creates a saved search for a user', function () {
    $user = User::factory()->create();

    $savedSearch = SavedSearch::factory()->create([
        'user_id' => $user->id,
        'name' => 'Makati Condos',
        'criteria' => [
            'property_type' => 'condo',
            'city' => 'Makati',
            'min_price' => 3000000,
        ],
    ]);

    expect($savedSearch->user->id)->toBe($user->id);
    expect($savedSearch->criteria['city'])->toBe('Makati');
});

it('scopes active saved searches', function () {
    $user = User::factory()->create();

    SavedSearch::factory()->active()->create(['user_id' => $user->id]);
    SavedSearch::factory()->active()->create(['user_id' => $user->id]);
    SavedSearch::factory()->inactive()->create(['user_id' => $user->id]);

    expect(SavedSearch::active()->where('user_id', $user->id)->count())->toBe(2);
});

it('deactivates a saved search', function () {
    $savedSearch = SavedSearch::factory()->active()->create();

    $savedSearch->deactivate();

    expect($savedSearch->fresh()->is_active)->toBeFalse();
});

it('marks a saved search as notified', function () {
    $savedSearch = SavedSearch::factory()->create(['last_notified_at' => null]);

    $savedSearch->markNotified();

    expect($savedSearch->fresh()->last_notified_at)->not->toBeNull();
});

it('builds a property query from saved criteria', function () {
    Property::factory()->for($this->store)->create([
        'property_type' => PropertyType::Condo,
        'listing_type' => ListingType::ForSale,
        'city' => 'Makati',
        'price' => 5000000,
        'status' => PropertyStatus::Active,
    ]);

    Property::factory()->for($this->store)->create([
        'property_type' => PropertyType::House,
        'listing_type' => ListingType::ForSale,
        'city' => 'Cebu City',
        'price' => 8000000,
        'status' => PropertyStatus::Active,
    ]);

    $savedSearch = SavedSearch::factory()->create([
        'criteria' => [
            'property_type' => 'condo',
            'city' => 'Makati',
        ],
    ]);

    expect($savedSearch->toPropertyQuery()->count())->toBe(1);
});

it('accesses saved searches through user relationship', function () {
    $user = User::factory()->create();
    SavedSearch::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->savedSearches)->toHaveCount(3);
});

// =========================================================
// Property Analytics Model
// =========================================================

it('records a daily view for a property', function () {
    $property = Property::factory()->for($this->store)->create();

    PropertyAnalytic::record($property->id, $this->store->id, ['views' => 1]);
    PropertyAnalytic::record($property->id, $this->store->id, ['views' => 1]);

    $analytic = PropertyAnalytic::where('property_id', $property->id)
        ->whereDate('date', now()->toDateString())
        ->first();

    expect($analytic->views)->toBe(2);
});

it('records multiple event types in one call', function () {
    $property = Property::factory()->for($this->store)->create();

    PropertyAnalytic::record($property->id, $this->store->id, [
        'views' => 1,
        'phone_clicks' => 1,
        'email_clicks' => 1,
    ]);

    $analytic = PropertyAnalytic::where('property_id', $property->id)->first();

    expect($analytic->views)->toBe(1);
    expect($analytic->phone_clicks)->toBe(1);
    expect($analytic->email_clicks)->toBe(1);
});

it('calculates store totals', function () {
    $property = Property::factory()->for($this->store)->create();

    PropertyAnalytic::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'date' => now()->toDateString(),
        'views' => 100,
        'inquiries' => 5,
    ]);

    PropertyAnalytic::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'date' => now()->subDay()->toDateString(),
        'views' => 50,
        'inquiries' => 3,
    ]);

    $totals = PropertyAnalytic::storeTotals($this->store->id);

    expect($totals['views'])->toBe(150);
    expect($totals['inquiries'])->toBe(8);
});

it('scopes analytics to last 30 days', function () {
    $property = Property::factory()->for($this->store)->create();

    PropertyAnalytic::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'date' => now()->subDays(5)->toDateString(),
        'views' => 20,
    ]);

    PropertyAnalytic::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'date' => now()->subDays(60)->toDateString(),
        'views' => 100,
    ]);

    $recent = PropertyAnalytic::forStore($this->store->id)->last30Days()->sum('views');

    expect($recent)->toBe(20);
});

// =========================================================
// Agent Profile (Store fields)
// =========================================================

it('stores agent profile data on the store', function () {
    $this->store->update([
        'agent_bio' => 'Licensed broker with 10 years experience.',
        'prc_license_number' => '0012345',
        'agent_certifications' => ['Licensed Real Estate Broker', 'CIPS'],
        'agent_specializations' => ['Luxury Properties', 'Condominiums'],
        'social_links' => ['facebook' => 'https://facebook.com/agent', 'instagram' => 'https://instagram.com/agent'],
    ]);

    $this->store->refresh();

    expect($this->store->agent_bio)->toBe('Licensed broker with 10 years experience.');
    expect($this->store->prc_license_number)->toBe('0012345');
    expect($this->store->agent_certifications)->toBeArray()->toHaveCount(2);
    expect($this->store->agent_specializations)->toContain('Luxury Properties');
    expect($this->store->social_links['facebook'])->toBe('https://facebook.com/agent');
});

// =========================================================
// Mortgage Calculator (Store fields)
// =========================================================

it('stores mortgage default settings on the store', function () {
    $this->store->update([
        'default_interest_rate' => 6.50,
        'default_loan_term_years' => 20,
        'default_down_payment_percent' => 20.00,
    ]);

    $this->store->refresh();

    expect((float) $this->store->default_interest_rate)->toBe(6.50);
    expect($this->store->default_loan_term_years)->toBe(20);
    expect((float) $this->store->default_down_payment_percent)->toBe(20.00);
});

// =========================================================
// Property - Floor Plans, Documents, Neighborhood
// =========================================================

it('stores floor plans as JSON on a property', function () {
    $property = Property::factory()->for($this->store)->create([
        'floor_plans' => [
            ['url' => 'https://example.com/floor1.jpg', 'label' => 'Ground Floor', 'floor_number' => 1],
            ['url' => 'https://example.com/floor2.jpg', 'label' => '2nd Floor', 'floor_number' => 2],
        ],
    ]);

    expect($property->floor_plans)->toBeArray()->toHaveCount(2);
    expect($property->floor_plans[0]['label'])->toBe('Ground Floor');
});

it('stores documents as JSON on a property', function () {
    $property = Property::factory()->for($this->store)->create([
        'documents' => [
            ['url' => 'https://example.com/brochure.pdf', 'name' => 'Project Brochure', 'type' => 'brochure', 'size_kb' => 2048],
            ['url' => 'https://example.com/prices.pdf', 'name' => 'Price List 2026', 'type' => 'price_list', 'size_kb' => 512],
        ],
    ]);

    expect($property->documents)->toBeArray()->toHaveCount(2);
    expect($property->documents[1]['type'])->toBe('price_list');
});

it('stores nearby places as JSON on a property', function () {
    $property = Property::factory()->for($this->store)->create([
        'nearby_places' => [
            ['name' => 'SM Makati', 'type' => 'mall', 'distance' => 0.5, 'distance_unit' => 'km'],
            ['name' => 'Makati Med', 'type' => 'hospital', 'distance' => 1.2, 'distance_unit' => 'km'],
            ['name' => 'Ayala MRT', 'type' => 'transport', 'distance' => 5, 'distance_unit' => 'min_walk'],
        ],
    ]);

    expect($property->nearby_places)->toBeArray()->toHaveCount(3);
    expect($property->nearby_places[2]['name'])->toBe('Ayala MRT');
});

// =========================================================
// Store Relationships (new)
// =========================================================

it('accesses open houses through the store relationship', function () {
    $property = Property::factory()->for($this->store)->create();
    OpenHouse::factory()->count(2)->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
    ]);

    expect($this->store->openHouses)->toHaveCount(2);
});

it('accesses testimonials through the store relationship', function () {
    Testimonial::factory()->count(3)->create(['store_id' => $this->store->id]);

    expect($this->store->testimonials)->toHaveCount(3);
});

it('accesses property analytics through the store relationship', function () {
    $property = Property::factory()->for($this->store)->create();

    PropertyAnalytic::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'date' => now()->toDateString(),
    ]);

    expect($this->store->propertyAnalytics)->toHaveCount(1);
});

// =========================================================
// Property Relationships (new)
// =========================================================

it('accesses open houses through the property relationship', function () {
    $property = Property::factory()->for($this->store)->create();
    OpenHouse::factory()->count(2)->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
    ]);

    expect($property->openHouses)->toHaveCount(2);
});

it('accesses analytics through the property relationship', function () {
    $property = Property::factory()->for($this->store)->create();
    PropertyAnalytic::factory()->create([
        'property_id' => $property->id,
        'store_id' => $this->store->id,
        'date' => now()->toDateString(),
    ]);

    expect($property->analytics)->toHaveCount(1);
});

// =========================================================
// Property Reviews (testimonials per listing)
// =========================================================

it('accesses testimonials through the property relationship', function () {
    $property = Property::factory()->for($this->store)->create();

    Testimonial::factory()->count(3)->published()->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
    ]);

    expect($property->testimonials)->toHaveCount(3);
});

it('calculates average rating for a property', function () {
    $property = Property::factory()->for($this->store)->create();

    Testimonial::factory()->published()->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
        'rating' => 5,
    ]);
    Testimonial::factory()->published()->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
        'rating' => 3,
    ]);

    expect($property->averageRating())->toBe(4.0);
});

it('returns null average rating when no reviews exist', function () {
    $property = Property::factory()->for($this->store)->create();

    expect($property->averageRating())->toBeNull();
});

it('only counts published reviews in average rating', function () {
    $property = Property::factory()->for($this->store)->create();

    Testimonial::factory()->published()->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
        'rating' => 5,
    ]);
    Testimonial::factory()->unpublished()->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
        'rating' => 1,
    ]);

    expect($property->averageRating())->toBe(5.0);
});

it('loads testimonials count and avg rating via eloquent', function () {
    $property = Property::factory()->for($this->store)->create();

    Testimonial::factory()->published()->count(3)->create([
        'store_id' => $this->store->id,
        'property_id' => $property->id,
        'rating' => 4,
    ]);

    $loaded = Property::query()
        ->withCount('testimonials')
        ->withAvg('testimonials as avg_rating', 'rating')
        ->find($property->id);

    expect($loaded->testimonials_count)->toBe(3);
    expect((float) $loaded->avg_rating)->toBe(4.0);
});
