<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'content' => fake()->paragraph(3),
            'type' => fake()->randomElement(['info', 'success', 'warning']),
            'is_pinned' => false,
            'is_active' => true,
            'target_roles' => null,
            'published_at' => now(),
            'expires_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
