<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateResearchProponentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('accessAdmin', User::class);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'proponents' => ['required', 'array', 'min:1', 'max:3'],
            'proponents.*.id' => ['required', 'string', 'exists:users,id'],
            'proponents.*.name' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $proponents = $this->input('proponents', []);
            $ids = collect($proponents)->pluck('id')->filter();
            if ($ids->unique()->count() !== $ids->count()) {
                $validator->errors()->add(
                    'proponents',
                    'Each proponent must be a different person.',
                );
            }

            foreach ($proponents as $i => $row) {
                $id = $row['id'] ?? null;
                if (! is_string($id) && ! is_int($id)) {
                    continue;
                }
                $eligible = User::query()
                    ->whereKey($id)
                    ->whereNull('blocked_at')
                    ->where(function ($q): void {
                        $q->whereDoesntHave('profile')
                            ->orWhereHas('profile', fn ($q2) => $q2->where('role', 'student'));
                    })
                    ->exists();
                if (! $eligible) {
                    $validator->errors()->add(
                        "proponents.{$i}.id",
                        'Each proponent must be an active student account.',
                    );
                }
            }
        });
    }
}
