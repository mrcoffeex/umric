<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $role = $request->query('role', 'student');
        if (! in_array($role, ['student', 'faculty'])) {
            $role = 'student';
        }
        $request->session()->put('oauth_role', $role);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $role = $request->session()->pull('oauth_role', 'student');

        // Restore a previously rejected (soft-deleted) account
        $existing = User::withTrashed()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($existing?->trashed()) {
            $existing->restore();
            $existing->fill([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
            ])->save();

            $profile = $existing->profile()->withTrashed()->first();
            if ($profile) {
                $profile->restore();
                $profile->update(['role' => $role, 'approved_at' => null]);
            } else {
                $existing->profile()->create(['role' => $role]);
            }

            Auth::login($existing, true);

            return redirect()->intended(route('dashboard'));
        }

        /** @var User $user */
        $user = User::firstOrCreate(
            ['google_id' => $googleUser->getId()],
            [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]
        );

        // Keep name/email in sync on subsequent logins
        $user->fill([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
        ])->save();

        if (! $user->profile) {
            $user->profile()->create(['role' => $role]);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
