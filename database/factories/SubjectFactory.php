<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => fake()->boolean(70) ? Program::factory() : null,
            'year_level' => fake()->numberBetween(1, 4),
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
