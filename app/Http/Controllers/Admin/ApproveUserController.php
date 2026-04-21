<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsAdminActions;
use App\Mail\FacultyRegistrationStatusMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ApproveUserController extends Controller
{
    use LogsAdminActions;

    public function approve(User $user): RedirectResponse
    {
        if (! $user->isFaculty()) {
            abort(400, 'Only faculty users can be approved.');
        }

        $user->profile()->update(['approved_at' => now()]);

        $this->logAdminAction()
            ->approve($user);

        Mail::to($user)->queue(new FacultyRegistrationStatusMail($user, 'approved'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "$user->name approved successfully."]);

        return back();
    }

    public function reject(User $user): RedirectResponse
    {
        if (! $user->isFaculty()) {
            abort(400, 'Only faculty users can be suspended.');
        }

        $user->profile()->update(['approved_at' => null]);

        $this->logAdminAction()
            ->reject($user);

        Mail::to($user)->queue(new FacultyRegistrationStatusMail($user, 'rejected'));

        Inertia::flash('toast', ['type' => 'warning', 'message' => "$user->name has been suspended."]);

        return back();
    }
}
