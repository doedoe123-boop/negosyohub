<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'locale' => 'en',
            'key' => 'common.example',
            'value' => $this->faker->sentence(),
        ];
    }
}
