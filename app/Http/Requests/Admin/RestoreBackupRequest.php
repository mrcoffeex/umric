<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RestoreBackupRequest extends FormRequest
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
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $filename = (string) $this->route('backup');

                if (! app(BackupService::class)->isValidFilename($filename)) {
                    $validator->errors()->add('backup', 'The backup filename is not valid.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
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
        ];
    }
}
