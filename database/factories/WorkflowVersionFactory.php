<?php

namespace Database\Factories;

use App\Models\ResearchPaper;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Models\WorkflowVersion;
use App\Support\WorkflowStepConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkflowVersion>
 */
class WorkflowVersionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numberBetween(1, 9999),
            'is_current' => false,
            'created_by' => null,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }

    public function createdBy(?User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user?->id,
        ]);
    }

    public function withDefaultSteps(): static
    {
        return $this->afterCreating(function (WorkflowVersion $version): void {
            foreach (ResearchPaper::STEPS as $index => $key) {
                WorkflowStep::factory()->create([
                    'workflow_version_id' => $version->id,
                    'key' => $key,
                    'label' => ResearchPaper::STEP_LABELS[$key] ?? $key,
                    'sort_order' => $index,
                    'config' => WorkflowStepConfig::defaultsFor($key),
                ]);
            }
        });
    }
}
