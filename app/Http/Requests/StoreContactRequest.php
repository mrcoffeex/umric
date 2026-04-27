<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('role') === '') {
            $this->merge(['role' => null]);
        }
    }

    /**
     * @return array<string, array<int, In|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'in:student,faculty,admin,other'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'message' => 'message',
        ];
    }
}
