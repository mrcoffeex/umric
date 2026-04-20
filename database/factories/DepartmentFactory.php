<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Department';

        return [
            'name' => $name,
            'code' => strtoupper(fake()->unique()->bothify('D??')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
