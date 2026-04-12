<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateResearchPaperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::id() === $this->route('paper')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'abstract' => ['sometimes', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sdg_id' => ['nullable', 'integer', 'exists:sdgs,id'],
            'agenda_id' => ['nullable', 'integer', 'exists:agendas,id'],
            'status' => ['sometimes', 'string', 'in:submitted,under_review,approved,presented,published,archived'],
            'keywords' => ['sometimes', 'string', 'max:500'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['string', 'max:255'],
            'proponents' => ['nullable', 'array'],
            'proponents.*' => ['string', 'max:255'],
            'file' => ['sometimes', 'file', 'mimes:pdf', 'max:50000'],
        ];
    }
}
