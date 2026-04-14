<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $section = fake()->randomElement(['A', 'B', 'C', 'D']);

        return [
            'section' => $section,
            'name' => "Class {$section}",
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
