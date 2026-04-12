<?php

namespace Database\Factories;

use App\Models\PaperFile;
use App\Models\ResearchPaper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaperFile>
 */
class PaperFileFactory extends Factory
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
            'file_name' => $this->faker->word().'.pdf',
            'file_path' => 'papers/'.$this->faker->uuid().'.pdf',
            'file_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'file_category' => $this->faker->randomElement(['paper', 'presentation', 'supplementary', 'data']),
            'disk' => 'local',
            'url' => null,
        ];
    }
}
