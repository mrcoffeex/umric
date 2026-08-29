<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DeleteBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('adminOnly', User::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $filename = (string) $this->route('backup');

                if (! app(BackupService::class)->isValidFilename($filename)) {
                    $validator->errors()->add('backup', 'The backup filename is not valid.');
                }
            },
        ];
    }
}
