<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

class SocialAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $role = $request->query('role');
        $isRegister = in_array($role, ['student', 'faculty'], true);

        if ($isRegister) {
            $request->session()->put('oauth_intent', 'register');
            $request->session()->put('oauth_role', $role);
        } else {
            $request->session()->put('oauth_intent', 'login');
            $request->session()->forget('oauth_role');
        }

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->stateless()->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
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

        $intent = $request->session()->pull('oauth_intent', 'login');
        $role = $request->session()->pull('oauth_role', 'student');

        $existing = User::withTrashed()
            ->where(function ($query) use ($googleUser) {
                $query->where('google_id', $googleUser->getId())
                    ->orWhere('email', $googleUser->getEmail());
            })
            ->first();

        if ($intent === 'register') {
            return $this->handleRegistration($existing, $googleUser, $role);
        }

        return $this->handleLogin($existing, $googleUser);
    }

    private function handleLogin(?User $existing, SocialiteUser $googleUser): RedirectResponse
    {
        if (! $existing || $existing->trashed() || ! $existing->profile) {
            return redirect()->route('register')->withErrors([
                'email' => 'No account found for this email. Please register and select your role first.',
            ]);
        }

        $this->syncGoogleAccount($existing, $googleUser);

        return $this->completeAuthentication($existing);
    }

    private function handleRegistration(?User $existing, SocialiteUser $googleUser, string $role): RedirectResponse
    {
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

            $this->syncGoogleAccount($existing, $googleUser);

            return $this->completeAuthentication($existing);
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

        return $this->completeAuthentication($user);
    }

    private function syncGoogleAccount(User $user, SocialiteUser $googleUser): void
    {
        $user->fill([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
        ])->save();
    }

    private function completeAuthentication(User $user): RedirectResponse
    {
        Auth::login($user, true);

        if ($user->isFaculty() && ! $user->isApproved()) {
            Auth::logout();

            return redirect()->route('registration.pending');
        }

        return redirect()->intended(route('dashboard'));
    }
}
