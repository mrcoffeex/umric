<?php

namespace Database\Factories;

use App\Models\EvaluationCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationCriterion>
 */
class EvaluationCriterionFactory extends Factory
{
    protected $model = EvaluationCriterion::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'max_points' => 25,
            'sort_order' => 0,
        ];
    }
}
