<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchPaper>
 */
class ResearchPaperFactory extends Factory
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
            'school_class_id' => null,
            'adviser_id' => null,
            'statistician_id' => null,
            'category_id' => Category::factory(),
            'title' => $this->faker->words(8, true),
            'abstract' => $this->faker->paragraphs(3, true),
            'proponents' => [],
            'sdg_ids' => [],
            'agenda_ids' => [],
            'tracking_id' => 'RP-'.strtoupper($this->faker->randomLetter().$this->faker->randomLetter().$this->faker->numberBetween(100000, 999999)),
            'status' => 'submitted',
            'current_step' => 'title_proposal',
            'step_ric_review' => null,
            'step_plagiarism' => null,
            'plagiarism_attempts' => 0,
            'plagiarism_score' => null,
            'step_outline_defense' => null,
            'outline_defense_schedule' => null,
            'step_data_gathering' => null,
            'step_rating' => null,
            'grade' => null,
            'step_final_manuscript' => null,
            'step_final_defense' => null,
            'final_defense_schedule' => null,
            'step_hard_bound' => null,
            'submission_date' => $this->faker->dateTimeBetween('-1 year'),
            'publication_date' => $this->faker->optional()->dateTime(),
            'keywords' => implode(', ', $this->faker->words(5)),
            'views' => $this->faker->numberBetween(0, 1000),
        ];
    }

    public function submittedBy(User $student): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $student->id,
            'proponents' => [[
                'id' => $student->id,
                'name' => $student->name,
            ]],
        ]);
    }

    public function forClass(SchoolClass $schoolClass): static
    {
        return $this->state(fn (array $attributes) => [
            'school_class_id' => $schoolClass->id,
        ]);
    }

    public function assignedTo(?User $adviser, ?User $statistician): static
    {
        return $this->state(fn (array $attributes) => [
            'adviser_id' => $adviser?->id,
            'statistician_id' => $statistician?->id,
        ]);
    }
}
