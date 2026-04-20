<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsAdminActions;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use LogsAdminActions;

    public function index(Request $request): Response
    {
        $users = User::with(['profile.department', 'profile.program'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role, fn ($q, $r) => $q->whereHas('profile', fn ($p) => $p->where('role', $r)))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role(),
                'avatar_url' => $u->profile?->avatarUrl(),
                'department' => $u->profile?->department?->name,
                'program' => $u->profile?->program?->name,
                'created_at' => $u->created_at->toDateString(),
                'approved_at' => $u->profile?->approved_at,
                'status' => $u->isBlocked()
                    ? 'blocked'
                    : ($u->isFaculty() && ! $u->isApproved() ? 'pending' : 'active'),
            ]);

        return Inertia::render('admin/Users/Index', [
            'users' => $users,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'programs' => Program::orderBy('name')->get(['id', 'name', 'department_id']),
            'filters' => $request->only('search', 'role'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,staff'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->profile()->create([
            'role' => $validated['role'],
            'approved_at' => now(),
        ]);

        // Log the action
        $this->logAdminAction()
            ->on($user)
            ->withProperties([
                'email' => $user->email,
                'role' => $validated['role'],
            ])
            ->withDescription("Created user {$user->email} with role {$validated['role']}")
            ->action('created');

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} has been created as {$validated['role']}."]);

        return redirect()->route('admin.users.index');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,faculty,staff,admin'],
        ]);

        $oldRole = $user->role();
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated,
        );

        // Log the action
        $this->logAdminAction()
            ->on($user)
            ->withProperties([
                'old_role' => $oldRole,
                'new_role' => $validated['role'],
            ])
            ->withDescription("Changed role from {$oldRole} to {$validated['role']}")
            ->action('updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role updated.']);

        return back();
    }

    public function block(User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403, 'Cannot block your own account.');

        if ($user->isBlocked()) {
            $user->update(['blocked_at' => null]);
            $this->logAdminAction()
                ->unblock($user);
            Inertia::flash('toast', ['type' => 'success', 'message' => "$user->name has been unblocked."]);
        } else {
            $user->update(['blocked_at' => now()]);
            $this->logAdminAction()
                ->block($user, 'Admin action');
            Inertia::flash('toast', ['type' => 'warning', 'message' => "$user->name has been blocked."]);
        }

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403, 'Cannot delete your own account.');

        $isPendingFaculty = $user->isFaculty() && ! $user->isApproved();
        $userEmail = $user->email;

        // Log before deletion
        $this->logAdminAction()
            ->on($user)
            ->withProperties(['was_pending_faculty' => $isPendingFaculty])
            ->withDescription($isPendingFaculty ? 'Rejected faculty registration' : "Deleted user {$userEmail}")
            ->action('deleted');

        $user->delete();

        $message = $isPendingFaculty ? 'Faculty registration rejected.' : 'User deleted.';
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
