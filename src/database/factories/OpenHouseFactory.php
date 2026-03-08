<?php

namespace Database\Factories;

use App\Models\OpenHouse;
use App\Models\Property;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenHouse>
 */
class OpenHouseFactory extends Factory
{
    protected $model = OpenHouse::class;

    /** @var list<string> */
    private array $titles = [
        'Open House Weekend',
        'Exclusive Property Viewing',
        'Sunday Open House',
        'Private Viewing Event',
        'Grand Open House',
        'VIP Property Tour',
        'Weekend Showcase',
        'Model Unit Viewing',
    ];

    public function definition(): array
    {
        $store = Store::factory()->create(['sector' => 'real_estate']);
        $eventDate = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'property_id' => Property::factory()->for($store),
            'store_id' => $store->id,
            'title' => fake()->randomElement($this->titles),
            'description' => fake()->optional(0.7)->paragraph(),
            'event_date' => $eventDate,
            'start_time' => fake()->randomElement(['09:00', '10:00', '13:00', '14:00']),
            'end_time' => fake()->randomElement(['12:00', '16:00', '17:00', '18:00']),
            'max_attendees' => fake()->optional(0.6)->numberBetween(5, 50),
            'is_virtual' => fake()->boolean(15),
            'virtual_link' => fn (array $attrs) => $attrs['is_virtual'] ? fake()->url() : null,
            'status' => 'scheduled',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'event_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function virtual(): static
    {
        return $this->state(fn () => [
            'is_virtual' => true,
            'virtual_link' => fake()->url(),
        ]);
    }
}
