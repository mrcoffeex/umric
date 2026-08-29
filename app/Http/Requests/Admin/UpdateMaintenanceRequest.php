<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('adminOnly', User::class);
    }

    protected function prepareForValidation(): void
    {
        $message = $this->input('message');

        if (is_array($message)) {
            $message = null;
        } elseif (is_string($message)) {
            $trimmed = trim($message);
            $message = $trimmed === '' ? null : $trimmed;
        } elseif ($message === '') {
            $message = null;
        }

        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'enabled' => 'maintenance mode',
            'message' => 'maintenance message',
        ];
    }
}
