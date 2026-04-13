<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ResearchPaper;
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
            'category_id' => Category::factory(),
            'title' => $this->faker->words(8, true),
            'abstract' => $this->faker->paragraphs(3, true),
            'proponents' => [['id' => 0, 'name' => 'Test Proponent']],
            'sdg_ids' => [],
            'agenda_ids' => [],
            'tracking_id' => 'RP-'.strtoupper($this->faker->randomLetter().$this->faker->randomLetter().$this->faker->numberBetween(100000, 999999)),
            'status' => $this->faker->randomElement(['submitted', 'under_review', 'approved', 'presented', 'published']),
            'submission_date' => $this->faker->dateTimeBetween('-1 year'),
            'publication_date' => $this->faker->optional()->dateTime(),
            'keywords' => implode(', ', $this->faker->words(5)),
            'views' => $this->faker->numberBetween(0, 1000),
        ];
    }
}
