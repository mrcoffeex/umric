<?php

namespace Database\Factories;

use App\Models\PanelDefense;
use App\Models\ResearchPaper;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanelDefense>
 */
class PanelDefenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(PanelDefense::DEFENSE_TYPES);

        return [
            'research_paper_id' => ResearchPaper::factory(),
            'defense_type' => fake()->randomElement($types),
            'panel_members' => fake()->randomElements(
                [
                    fake()->name(),
                    fake()->name(),
                    fake()->name(),
                    fake()->name(),
                ],
                fake()->numberBetween(2, 3),
            ),
            'schedule' => fake()->optional()->dateTimeBetween('-1 month', '+2 months'),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
