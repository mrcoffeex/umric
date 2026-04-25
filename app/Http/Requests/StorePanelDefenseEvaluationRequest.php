<?php

namespace App\Http\Requests;

use App\Models\EvaluationCriterion;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePanelDefenseEvaluationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('comments') && is_string($this->input('comments'))) {
            $this->merge(['comments' => trim($this->input('comments'))]);
        }
    }

    public function panelDefense(): PanelDefense
    {
        $defense = $this->route('panelDefense');
        if (! $defense instanceof PanelDefense) {
            abort(404);
        }

        return $defense;
    }

    public function authorize(): bool
    {
        if (! $this->user() || ! $this->user()->isApproved()) {
            return false;
        }

        return $this->panelDefense()->includesPanelMember($this->user());
    }

    /**
     * @return array<string, list<string|object>>
     */
    public function rules(): array
    {
        return [
            'comments' => ['required', 'string', 'min:1', 'max:20000'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['required', 'integer', 'min:0'],
            'q' => ['nullable', 'string', 'max:200'],
            'defense_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
            'schedule_date' => ['nullable', 'string', 'date_format:Y-m-d'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $exists = PanelDefenseEvaluation::query()
                ->where('panel_defense_id', $this->panelDefense()->id)
                ->where('evaluator_id', (string) $this->user()->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('scores', __('You have already submitted an evaluation. Scores are final and cannot be changed.'));

                return;
            }

            $defense = $this->panelDefense();
            $defense->loadMissing('evaluationFormat');
            $format = $defense->evaluationFormat;
            if (! $format) {
                $validator->errors()->add('scores', __('This defense is missing an evaluation format. Contact an admin.'));

                return;
            }

            $totalMax = (int) EvaluationCriterion::query()
                ->where('evaluation_format_id', $format->id)
                ->sum('max_points');
            $criterionCount = EvaluationCriterion::query()
                ->where('evaluation_format_id', $format->id)
                ->count();

            if ($format->isChecklist()) {
                if ($criterionCount < 1) {
                    $validator->errors()->add('scores', __('This checklist has no items yet. Contact an admin.'));

                    return;
                }
            } elseif ($criterionCount < 1) {
                $validator->errors()->add('scores', __('Scoring is unavailable until this defense has at least one criterion. Contact an admin.'));

                return;
            } elseif ($format->scoringUsesWeights() && $totalMax !== EvaluationCriterion::MAX_TOTAL) {
                $validator->errors()->add('scores', __('Scoring (weighted) is unavailable until criterion weights total 100%. Contact an admin.'));

                return;
            }

            $criteria = EvaluationCriterion::query()
                ->where('evaluation_format_id', $format->id)
                ->orderBy('sort_order')
                ->get();
            $scores = (array) $this->input('scores', []);
            $keys = $criteria->pluck('id')->map(fn (string $id) => (string) $id)->all();

            if (array_diff($keys, array_keys($scores)) || array_diff(array_keys($scores), $keys)) {
                $validator->errors()->add('scores', __('Submit a response for every criterion.'));

                return;
            }

            if ($format->isChecklist()) {
                foreach ($criteria as $c) {
                    $id = (string) $c->id;
                    if (! array_key_exists($id, $scores)) {
                        $validator->errors()->add("scores.{$id}", __('This item is required.'));

                        continue;
                    }
                    $v = (int) $scores[$id];
                    if ($v !== 0 && $v !== 1) {
                        $validator->errors()->add("scores.{$id}", __('Each item must be Yes (1) or No (0).'));
                    }
                }

                return;
            }

            foreach ($criteria as $c) {
                $id = (string) $c->id;
                if (! array_key_exists($id, $scores)) {
                    $validator->errors()->add("scores.{$id}", __('This criterion is required.'));

                    continue;
                }
                $v = (int) $scores[$id];
                if ($v < 0 || $v > 100) {
                    $validator->errors()->add("scores.{$id}", __('The score for :name must be between 0 and 100.', [
                        'name' => $c->name,
                    ]));
                }
            }
        });
    }
}
