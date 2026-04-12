<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ApproveUserController extends Controller
{
    public function approve(User $user): RedirectResponse
    {
        if (! $user->isFaculty()) {
            abort(400, 'Only faculty users can be approved.');
        }

        $user->profile()->update(['approved_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "$user->name approved successfully."]);

        return back();
    }

    public function reject(User $user): RedirectResponse
    {
        if (! $user->isFaculty()) {
            abort(400, 'Only faculty users can be suspended.');
        }

        $user->profile()->update(['approved_at' => null]);

        Inertia::flash('toast', ['type' => 'warning', 'message' => "$user->name has been suspended."]);

        return back();
    }
}
