<?php

namespace Database\Factories;

use App\Models\EvaluationFormat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationFormat>
 */
class EvaluationFormatFactory extends Factory
{
    protected $model = EvaluationFormat::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' rubric',
            'evaluation_type' => EvaluationFormat::TYPE_SCORING,
            'use_weights' => false,
        ];
    }
}
