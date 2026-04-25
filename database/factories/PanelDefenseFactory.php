<?php

namespace Database\Factories;

use App\Models\EvaluationFormat;
use App\Models\PanelDefense;
use App\Models\ResearchPaper;
use App\Models\User;
use App\Support\PanelDefenseSchedule;
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
            'evaluation_format_id' => function () {
                $id = EvaluationFormat::query()->value('id');
                if ($id !== null) {
                    return $id;
                }

                return EvaluationFormat::factory()->create()->id;
            },
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
            'schedule' => fn () => fake()->boolean(75)
                ? PanelDefenseSchedule::combineToCarbon(
                    fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
                    fake()->randomElement(PanelDefenseSchedule::allowedTimeValues()),
                )
                : null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function forPaper(ResearchPaper $paper): static
    {
        return $this->state(fn (array $attributes) => [
            'research_paper_id' => $paper->id,
        ]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'defense_type' => $type,
        ]);
    }

    public function withMembers(array $members): static
    {
        return $this->state(fn (array $attributes) => [
            'panel_members' => array_values($members),
        ]);
    }
}
