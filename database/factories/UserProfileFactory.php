<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Program;
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
        $role = fake()->randomElement(['student', 'faculty', 'staff']);

        return [
            'user_id' => User::factory(),
            'role' => $role,
            'department_id' => null,
            'program_id' => null,
            'specialization' => fake()->optional()->words(3, true),
            'institution' => fake()->optional()->company(),
            'degree' => fake()->optional()->randomElement(['BS', 'MS', 'MA', 'PhD']),
            'graduation_year' => fake()->optional()->year(),
            'bio' => fake()->optional()->paragraph(),
            'presentations' => fake()->boolean(25) ? [fake()->sentence()] : null,
            'achievements' => fake()->boolean(20) ? [fake()->sentence()] : null,
            'avatar_disk' => 'local',
            'approved_at' => $role === 'faculty' ? now()->subDays(fake()->numberBetween(1, 365)) : null,
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

    public function forAcademicUnit(?Department $department, ?Program $program = null): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department?->id,
            'program_id' => $program?->id,
        ]);
    }
}
