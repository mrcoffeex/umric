<?php

namespace Database\Factories;

use App\Models\EvaluationCriterion;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanelDefenseEvaluation>
 */
class PanelDefenseEvaluationFactory extends Factory
{
    protected $model = PanelDefenseEvaluation::class;

    public function definition(): array
    {
        $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get();
        $lineItems = [];
        $sum = 0;
        foreach ($criteria as $c) {
            $s = random_int(0, (int) $c->max_points);
            $lineItems[] = [
                'criterion_id' => (string) $c->id,
                'name' => $c->name,
                'max_points' => (int) $c->max_points,
                'score' => $s,
            ];
            $sum += $s;
        }

        return [
            'panel_defense_id' => PanelDefense::factory(),
            'evaluator_id' => User::factory(),
            'line_items' => $lineItems,
            'final_score' => $sum,
        ];
    }
}
