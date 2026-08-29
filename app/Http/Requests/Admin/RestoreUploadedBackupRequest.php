<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class RestoreUploadedBackupRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('adminOnly', User::class);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<int, mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
            'confirmation' => ['accepted'],
            'file' => [
                'required',
                File::types(['zip'])
                    ->max((int) config('backup.max_upload_kilobytes', 262144)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'backup file',
            'confirmation' => 'confirmation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.accepted' => 'Confirm that you understand this will replace all current data.',
            'file.required' => 'Choose a backup archive to restore.',
        ];
    }
}
