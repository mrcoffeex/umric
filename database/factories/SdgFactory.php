<?php

namespace Database\Factories;

use App\Models\Sdg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sdg>
 */
class SdgFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numberBetween(1, 17),
            'name' => fake()->words(4, true),
            'code' => 'SDG-'.str_pad((string) fake()->unique()->numberBetween(1, 17), 2, '0', STR_PAD_LEFT),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->optional()->hexColor(),
            'is_active' => true,
        ];
    }
}
