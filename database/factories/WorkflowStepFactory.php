<?php

namespace Database\Factories;

use App\Models\WorkflowStep;
use App\Models\WorkflowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowStep>
 */
class WorkflowStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'workflow_version_id' => WorkflowVersion::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => ucfirst($label),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
