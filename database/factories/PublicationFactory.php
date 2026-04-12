<?php

namespace Database\Factories;

use App\Models\Publication;
use App\Models\ResearchPaper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'research_paper_id' => ResearchPaper::factory(),
            'journal_name' => $this->faker->words(3, true),
            'publisher' => $this->faker->company(),
            'publication_date' => $this->faker->dateTime(),
            'volume' => $this->faker->numberBetween(1, 50),
            'issue' => $this->faker->numberBetween(1, 12),
            'pages' => $this->faker->numberBetween(1, 100).'-'.$this->faker->numberBetween(101, 200),
            'doi' => '10.'.$this->faker->numberBetween(1000, 5000).'/'.str()->slug($this->faker->slug()),
            'isbn' => $this->faker->isbn13(),
        ];
    }
}
