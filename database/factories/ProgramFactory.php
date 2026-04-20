<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'department_id' => Department::factory(),
            'name' => ucfirst($name),
            'code' => strtoupper(fake()->unique()->bothify('PRG###')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
