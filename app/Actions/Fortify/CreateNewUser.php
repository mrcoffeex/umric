<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['sometimes', 'string', 'in:student,faculty,staff'],
        ])->validate();

        // Restore a previously rejected (soft-deleted) account with the same email
        $role = $input['role'] ?? 'student';
        $existing = User::withTrashed()->where('email', $input['email'])->first();

        if ($existing?->trashed()) {
            $existing->restore();
            $profile = $existing->profile()->withTrashed()->first();
            if ($profile) {
                $profile->restore();
                $profile->update(['role' => $role, 'approved_at' => null]);
            } else {
                $existing->profile()->create(['role' => $role, 'approved_at' => null]);
            }
            $existing->update(['password' => $input['password']]);

            return $existing;
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $user->profile()->create(['role' => $role]);

        return $user;
    }
}
