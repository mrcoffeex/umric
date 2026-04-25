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
        return [
            'panel_defense_id' => PanelDefense::factory(),
            'evaluator_id' => User::factory(),
            'line_items' => [],
            'final_score' => 0,
            'comments' => 'Factory evaluation comment.',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (PanelDefenseEvaluation $eval): void {
            $defense = $eval->panelDefense;
            if (! $defense) {
                return;
            }

            $criteria = EvaluationCriterion::query()
                ->where('evaluation_format_id', $defense->evaluation_format_id)
                ->orderBy('sort_order')
                ->get();

            $lineItems = [];
            $defense->loadMissing('evaluationFormat');
            $format = $defense->evaluationFormat;
            $useWeights = $format?->scoringUsesWeights() ?? true;

            foreach ($criteria as $c) {
                $s = random_int(0, 100);
                $lineItems[] = [
                    'criterion_id' => (string) $c->id,
                    'name' => $c->name,
                    'content' => $c->content,
                    'max_points' => (int) $c->max_points,
                    'score' => $s,
                ];
            }

            if ($format?->isChecklist() ?? false) {
                $sum = array_sum(array_column($lineItems, 'score'));
            } elseif ($useWeights) {
                $sum = 0.0;
                foreach ($lineItems as $row) {
                    $sum += ((int) $row['score'] / 100.0) * (int) $row['max_points'];
                }
                $sum = (int) round($sum);
            } else {
                $ss = array_column($lineItems, 'score');
                $n = count($ss);
                $sum = $n > 0 ? (int) round(array_sum($ss) / $n) : 0;
            }

            $eval->update([
                'line_items' => $lineItems,
                'final_score' => $sum,
            ]);
        });
    }
}
