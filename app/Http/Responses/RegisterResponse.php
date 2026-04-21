<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user?->isFaculty()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('registration.pending');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
