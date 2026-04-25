<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResearchPaperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('paper'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'abstract' => ['sometimes', 'required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sdg_ids' => ['sometimes', 'nullable', 'array'],
            'sdg_ids.*' => ['integer'],
            'agenda_ids' => ['sometimes', 'nullable', 'array'],
            'agenda_ids.*' => ['integer'],
            'status' => ['sometimes', 'string', 'in:submitted,under_review,approved,presented,published,archived'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'proponents' => ['nullable', 'array'],
            'proponents.*.id' => ['nullable', 'integer'],
            'proponents.*.name' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf', 'max:'.config('uploads.max_size_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'abstract' => 'rationale',
        ];
    }
}
