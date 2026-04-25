<?php

namespace App\Http\Requests;

use App\Models\PanelDefenseEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePanelDefenseEvaluationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('comments') && is_string($this->input('comments'))) {
            $this->merge(['comments' => trim($this->input('comments'))]);
        }
    }

    public function panelDefenseEvaluation(): PanelDefenseEvaluation
    {
        $model = $this->route('panelDefenseEvaluation');
        if (! $model instanceof PanelDefenseEvaluation) {
            abort(404);
        }

        return $model;
    }

    public function authorize(): bool
    {
        if (! $this->user() || ! $this->user()->isApproved()) {
            return false;
        }

        return $this->user()->hasRole('admin', 'staff');
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
            $model = $this->panelDefenseEvaluation();
            $lineItems = $model->line_items;
            if (! is_array($lineItems) || $lineItems === []) {
                $validator->errors()->add('scores', __('This evaluation is missing score lines.'));

                return;
            }

            $defense = $model->panelDefense;
            if (! $defense) {
                $validator->errors()->add('scores', __('Invalid evaluation.'));

                return;
            }
            $defense->loadMissing('evaluationFormat');
            $format = $defense->evaluationFormat;
            $isChecklist = $format?->isChecklist() ?? false;

            $scores = (array) $this->input('scores', []);
            foreach ($lineItems as $row) {
                if (! is_array($row) || ! isset($row['criterion_id'], $row['max_points'])) {
                    $validator->errors()->add('scores', __('Invalid evaluation snapshot.'));

                    return;
                }
                $id = (string) $row['criterion_id'];
                if (! array_key_exists($id, $scores)) {
                    $validator->errors()->add("scores.{$id}", __('Every criterion in this evaluation is required.'));

                    continue;
                }
                $v = (int) $scores[$id];
                if ($isChecklist) {
                    if ($v !== 0 && $v !== 1) {
                        $validator->errors()->add("scores.{$id}", __('Each item must be Yes (1) or No (0).'));
                    }
                } else {
                    if ($v < 0 || $v > 100) {
                        $validator->errors()->add("scores.{$id}", __('Each score must be between 0 and 100.'));
                    }
                }
            }
        });
    }
}
