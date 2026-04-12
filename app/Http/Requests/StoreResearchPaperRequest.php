<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreResearchPaperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sdg_id' => ['nullable', 'integer', 'exists:sdgs,id'],
            'agenda_id' => ['nullable', 'integer', 'exists:agendas,id'],
            'status' => ['sometimes', 'string', 'in:submitted,under_review,approved,presented,published,archived'],
            'keywords' => ['sometimes', 'string', 'max:500'],
            'authors' => ['sometimes', 'array'],
            'authors.*' => ['string', 'max:255'],
            'proponents' => ['required', 'array', 'min:1', 'max:3'],
            'proponents.*.id' => ['required', 'integer', 'exists:users,id'],
            'proponents.*.name' => ['required', 'string', 'max:255'],
            'file' => ['sometimes', 'file', 'mimes:pdf', 'max:50000'],
        ];
    }
}
