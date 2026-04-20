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
        $yearStart = fake()->numberBetween((int) now()->format('Y') - 1, (int) now()->format('Y') + 1);
        $yearEnd = $yearStart + 1;
        $semester = fake()->numberBetween(1, 2);
        $section = fake()->randomElement(['A', 'B', 'C', 'D']);
        $codePrefix = strtoupper(fake()->lexify('CLS'));

        return [
            'name' => "{$codePrefix} {$section}",
            'class_code' => "{$codePrefix}{$section}-S{$semester}-".substr((string) $yearStart, -2).substr((string) $yearEnd, -2),
            'school_year' => "{$yearStart}-{$yearEnd}",
            'semester' => $semester,
            'term' => null,
            'section' => $section,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'join_code' => fake()->boolean(40) ? strtoupper(fake()->bothify('??##??##')) : null,
        ];
    }
}
