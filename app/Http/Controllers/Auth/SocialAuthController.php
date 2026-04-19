<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class SocialAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $role = $request->query('role', 'student');
        if (! in_array($role, ['student', 'faculty'])) {
            $role = 'student';
        }
        $request->session()->put('oauth_role', $role);

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->stateless()->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        // Handle Google OAuth errors (user denied, etc.)
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in was cancelled or denied.',
            ]);
        }

        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed. Please try again.',
            ]);
        }

        $role = $request->session()->pull('oauth_role', 'student');

        // Find by google_id or email (including soft-deleted)
        $existing = User::withTrashed()
            ->where(function ($query) use ($googleUser) {
                $query->where('google_id', $googleUser->getId())
                    ->orWhere('email', $googleUser->getEmail());
            })
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();

                $profile = $existing->profile()->withTrashed()->first();
                if ($profile) {
                    $profile->restore();
                    $profile->update(['role' => $role, 'approved_at' => null]);
                } else {
                    $existing->profile()->create(['role' => $role]);
                }
            } elseif (! $existing->profile) {
                $existing->profile()->create(['role' => $role]);
            }

            $existing->fill([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
            ])->save();

            Auth::login($existing, true);

            if ($existing->isFaculty() && ! $existing->isApproved()) {
                Auth::logout();

                return redirect()->route('login')
                    ->with('status', 'This account is not approved yet');
            }

            return redirect()->intended(route('dashboard'));
        }

        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => bcrypt(Str::random(32)),
        ]);

        $user->email_verified_at = now();
        $user->save();

        $user->profile()->create(['role' => $role]);

        Auth::login($user, true);

        if ($user->isFaculty() && ! $user->isApproved()) {
            Auth::logout();

            return redirect()->route('login')
                ->with('status', 'This account is not approved yet');
        }

        return redirect()->intended(route('dashboard'));
    }
}
