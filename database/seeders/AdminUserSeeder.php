<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create or update the production admin account.
     *
     * Configure via:
     * - SEED_ADMIN_NAME
     * - SEED_ADMIN_EMAIL
     * - SEED_ADMIN_PASSWORD
     */
    public function run(): void
    {
        $name = (string) env('SEED_ADMIN_NAME', 'System Administrator');
        $email = strtolower(trim((string) env('SEED_ADMIN_EMAIL', 'admin@umdcric.com')));
        $password = (string) env('SEED_ADMIN_PASSWORD', 'ChangeMe!Admin123');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('SEED_ADMIN_EMAIL must be a valid email address.');

            return;
        }

        if (strlen($password) < 8) {
            $this->command?->error('SEED_ADMIN_PASSWORD must be at least 8 characters.');

            return;
        }

        $user = User::query()->withTrashed()->where('email', $email)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
        } else {
            if ($user->trashed()) {
                $user->restore();
            }

            $user->forceFill([
                'name' => $name,
                'password' => $password,
                'blocked_at' => null,
            ])->save();
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $profile = UserProfile::withTrashed()->firstOrNew(['user_id' => $user->id]);
        if ($profile->trashed()) {
            $profile->restore();
        }
        $profile->fill([
            'role' => 'admin',
            'department_id' => null,
            'program_id' => null,
            'approved_at' => null,
        ])->save();

        $this->command?->info("Admin ready: {$email}");
    }
}
