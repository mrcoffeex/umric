<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\BackupScheduleService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('adminOnly', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'frequency' => ['required', 'string', Rule::in(BackupScheduleService::FREQUENCIES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'enabled' => 'automatic backups',
            'frequency' => 'backup frequency',
        ];
    }
}
