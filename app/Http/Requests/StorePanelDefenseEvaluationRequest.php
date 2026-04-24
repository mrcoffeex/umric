<?php

namespace App\Http\Requests;

use App\Models\EvaluationCriterion;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePanelDefenseEvaluationRequest extends FormRequest
{
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
            'scores' => ['required', 'array', 'min:1'],
            'scores.*' => ['required', 'integer', 'min:0'],
            'q' => ['nullable', 'string', 'max:200'],
            'defense_type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:100'],
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

            $totalMax = (int) EvaluationCriterion::query()->sum('max_points');
            if (EvaluationCriterion::query()->count() < 1 || $totalMax !== EvaluationCriterion::MAX_TOTAL) {
                $validator->errors()->add('scores', __('Scoring is unavailable until the criteria are configured to total 100 points. Contact an admin.'));

                return;
            }

            $criteria = EvaluationCriterion::query()->orderBy('sort_order')->get();
            $scores = (array) $this->input('scores', []);
            $keys = $criteria->pluck('id')->map(fn (string $id) => (string) $id)->all();

            if (array_diff($keys, array_keys($scores)) || array_diff(array_keys($scores), $keys)) {
                $validator->errors()->add('scores', __('Submit a score for every criterion.'));

                return;
            }

            foreach ($criteria as $c) {
                $id = (string) $c->id;
                if (! array_key_exists($id, $scores)) {
                    $validator->errors()->add("scores.{$id}", __('This criterion is required.'));

                    continue;
                }
                $v = (int) $scores[$id];
                if ($v < 0 || $v > (int) $c->max_points) {
                    $validator->errors()->add("scores.{$id}", __('The score for :name must be between 0 and :max points.', [
                        'name' => $c->name,
                        'max' => (int) $c->max_points,
                    ]));
                }
            }
        });
    }
}
