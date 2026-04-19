<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['student', 'faculty', 'staff']),
            'department' => fake()->optional()->randomElement(['computer_science', 'engineering', 'business', 'science', 'humanities', 'other']),
            'specialization' => fake()->optional()->words(3, true),
            'institution' => fake()->optional()->company(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'staff',
        ]);
    }

    public function faculty(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'faculty',
            'approved_at' => now(),
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'student',
        ]);
    }
}
