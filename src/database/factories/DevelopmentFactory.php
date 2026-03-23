<?php

namespace Database\Factories;

use App\Models\Development;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Development>
 */
class DevelopmentFactory extends Factory
{
    protected $model = Development::class;

    /** @var list<string> */
    private const DEVELOPMENT_NAMES = [
        'The Residences at %s',
        '%s Tower',
        '%s Place',
        '%s Heights',
        '%s Gardens',
        'One %s',
        'Vista %s',
        'Grand %s Estates',
    ];

    /** @var list<string> */
    private const LANDMARKS = [
        'Ortigas', 'Makati', 'BGC', 'Eastwood', 'Alabang',
        'Capitol Commons', 'Cebu Park', 'Davao Bay',
        'Iloilo River', 'Clark Green', 'Tagaytay Ridge',
    ];

    /** @var list<string> */
    private const DEVELOPERS = [
        'Ayala Land', 'SM Development', 'Megaworld', 'Robinsons Land',
        'DMCI Homes', 'Federal Land', 'Vista Land', 'Filinvest',
    ];

    /** @var list<string> */
    private const TYPES = [
        'condominium', 'subdivision', 'township', 'mixed_use', 'commercial_complex',
    ];

    /** @var list<string> */
    private const AMENITIES = [
        'Swimming Pool', 'Gym', 'Clubhouse', 'Function Room',
        'Playground', 'Jogging Path', 'Basketball Court',
        'Landscaped Garden', 'Sky Lounge', 'Co-Working Space',
        'Concierge', 'Retail Area', 'Covered Parking',
        'CCTV Surveillance', 'Fire Alarm System',
    ];

    /** @var list<string> */
    private const CITIES = [
        'Makati', 'Taguig', 'Pasig', 'Mandaluyong', 'Quezon City',
        'Cebu City', 'Davao City', 'Iloilo City', 'Parañaque',
    ];

    /** @var list<string> */
    private const PROVINCES = [
        'Metro Manila', 'Cebu', 'Davao del Sur', 'Iloilo',
        'Rizal', 'Cavite', 'Laguna', 'Batangas',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $landmark = fake()->randomElement(self::LANDMARKS);
        $template = fake()->randomElement(self::DEVELOPMENT_NAMES);
        $name = sprintf($template, $landmark);
        $totalUnits = fake()->numberBetween(20, 500);

        return [
            'store_id' => Store::factory()->state(['sector' => 'real_estate']),
            'name' => $name,
            'slug' => null,
            'description' => fake()->paragraphs(2, true),
            'developer_name' => fake()->randomElement(self::DEVELOPERS),
            'development_type' => fake()->randomElement(self::TYPES),
            'status' => 'active',
            'address_line' => fake()->streetAddress(),
            'barangay' => 'Brgy. '.fake()->word(),
            'city' => fake()->randomElement(self::CITIES),
            'province' => fake()->randomElement(self::PROVINCES),
            'zip_code' => fake()->numerify('####'),
            'latitude' => fake()->latitude(14.4, 14.7),
            'longitude' => fake()->longitude(120.9, 121.1),
            'total_units' => $totalUnits,
            'available_units' => fake()->numberBetween(1, $totalUnits),
            'floors' => fake()->numberBetween(5, 50),
            'year_built' => fake()->numberBetween(2018, 2027),
            'price_range_min' => $min = fake()->numberBetween(2, 10) * 1_000_000,
            'price_range_max' => fake()->numberBetween(max(12, (int) ($min / 1_000_000) + 4), 50) * 1_000_000,
            'amenities' => fake()->randomElements(self::AMENITIES, fake()->numberBetween(4, 10)),
            'images' => null,
            'logo' => null,
            'website_url' => fake()->optional()->url(),
            'video_url' => null,
            'is_featured' => false,
            'published_at' => now(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function subdivision(): static
    {
        return $this->state(fn () => [
            'development_type' => 'subdivision',
            'floors' => null,
        ]);
    }
}
