<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\WorkflowStepConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('accessAdmin', User::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'steps' => ['required', 'array', 'min:2'],
            'steps.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
            'steps.*.label' => ['required', 'string', 'max:100'],
            'steps.*.config' => ['nullable', 'array'],
            'steps.*.config.statuses' => ['nullable', 'array'],
            'steps.*.config.statuses.*.value' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'steps.*.config.statuses.*.label' => ['required', 'string', 'max:80'],
            'steps.*.config.statuses.*.color' => ['required', 'string', Rule::in(WorkflowStepConfig::colors())],
            'steps.*.config.statuses.*.completes' => ['required', 'boolean'],
            'steps.*.config.inputs' => ['nullable', 'array'],
            'steps.*.config.inputs.*.key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'steps.*.config.inputs.*.label' => ['required', 'string', 'max:80'],
            'steps.*.config.inputs.*.type' => ['required', 'string', Rule::in(WorkflowStepConfig::inputTypes())],
            'steps.*.config.inputs.*.show_on_calendar' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'steps' => 'steps',
            'steps.*.key' => 'step key',
            'steps.*.label' => 'step label',
            'steps.*.config.statuses.*.value' => 'status value',
            'steps.*.config.statuses.*.label' => 'status label',
            'steps.*.config.statuses.*.color' => 'status color',
            'steps.*.config.inputs.*.key' => 'input key',
            'steps.*.config.inputs.*.label' => 'input label',
            'steps.*.config.inputs.*.type' => 'input type',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'steps.min' => 'Add at least two steps.',
            'steps.*.key.regex' => 'Use a lowercase slug such as ethics_review.',
            'steps.*.key.distinct' => 'Each step key must be unique.',
            'steps.*.config.statuses.*.value.regex' => 'Use a lowercase slug such as approved.',
            'steps.*.config.inputs.*.key.regex' => 'Use a lowercase slug such as reviewer_name.',
        ];
    }

    /**
     * @return list<\Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $steps = $this->input('steps');
                if (! is_array($steps) || $steps === []) {
                    return;
                }

                $last = $steps[array_key_last($steps)];
                $lastKey = is_array($last) ? ($last['key'] ?? null) : null;

                if ($lastKey !== 'completed') {
                    $validator->errors()->add('steps', 'The last step must be the completed milestone.');
                }

                foreach ($steps as $index => $step) {
                    if (! is_array($step)) {
                        continue;
                    }

                    $key = $step['key'] ?? null;
                    $config = $step['config'] ?? null;
                    if (! is_array($config)) {
                        continue;
                    }

                    $statusValues = collect($config['statuses'] ?? [])
                        ->filter(fn (mixed $status): bool => is_array($status))
                        ->pluck('value')
                        ->filter();

                    if ($statusValues->count() !== $statusValues->unique()->count()) {
                        $validator->errors()->add(
                            "steps.{$index}.config.statuses",
                            'Each status value on this step must be unique.',
                        );
                    }

                    $inputKeys = collect($config['inputs'] ?? [])
                        ->filter(fn (mixed $input): bool => is_array($input))
                        ->pluck('key')
                        ->filter();

                    if ($inputKeys->count() !== $inputKeys->unique()->count()) {
                        $validator->errors()->add(
                            "steps.{$index}.config.inputs",
                            'Each input key on this step must be unique.',
                        );
                    }

                    if (in_array($key, ['title_proposal', 'completed'], true)) {
                        continue;
                    }

                    $hasCompleting = collect($config['statuses'] ?? [])
                        ->contains(fn (mixed $status): bool => is_array($status) && ($status['completes'] ?? false));

                    if (($config['statuses'] ?? []) !== [] && ! $hasCompleting) {
                        $validator->errors()->add(
                            "steps.{$index}.config.statuses",
                            'Mark at least one status as finishing this step.',
                        );
                    }
                }
            },
        ];
    }
}
