<?php

namespace Database\Factories;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
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
            'evaluation_format_id' => EvaluationFormat::factory(),
            'content' => '<p>'.e(fake()->sentence(3), false).'</p>',
            'max_points' => 100,
            'sort_order' => 0,
        ];
    }
}
