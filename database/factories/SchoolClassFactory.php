<?php

namespace Database\Factories;

use App\Models\Program;
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
        $yearLevel = fake()->numberBetween(1, 4);
        $section = fake()->randomElement(['A', 'B', 'C', 'D']);

        return [
            'program_id' => Program::factory(),
            'year_level' => $yearLevel,
            'section' => $section,
            'name' => "Class {$yearLevel}-{$section}",
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
