<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Department;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user    = $request->user();
        $profile = $user->profile;

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => $request->session()->get('status'),
            'departments'     => Department::select('id', 'name')
                ->with('programs:id,name,department_id')
                ->orderBy('name')
                ->get(),
            'profile'         => $profile ? [
                'role'          => $profile->role,
                'department_id' => $profile->department_id,
                'program_id'    => $profile->program_id,
                'specialization'=> $profile->specialization,
                'institution'   => $profile->institution,
                'degree'        => $profile->degree,
                'graduation_year'=> $profile->graduation_year,
                'bio'           => $profile->bio,
                'avatar_url'    => $profile->avatarUrl(),
            ] : null,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bio'             => ['nullable', 'string', 'max:1000'],
            'specialization'  => ['nullable', 'string', 'max:255'],
            'institution'     => ['nullable', 'string', 'max:255'],
            'degree'          => ['nullable', 'string', 'max:100'],
            'graduation_year' => ['nullable', 'string', 'max:4'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'program_id'      => ['nullable', 'integer', 'exists:programs,id'],
        ]);

        $request->user()->profile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile details updated.']);

        return to_route('profile.edit');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user    = $request->user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        // Delete old avatar
        if ($profile->profile_photo) {
            Storage::disk($profile->avatar_disk ?? 'public')->delete($profile->profile_photo);
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $path = $request->file('avatar')->store('avatars', $disk);

        $profile->update([
            'profile_photo' => $path,
            'avatar_disk'   => $disk,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Avatar updated.']);

        return to_route('profile.edit');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        if ($profile && $profile->profile_photo) {
            Storage::disk($profile->avatar_disk ?? 'public')->delete($profile->profile_photo);
            $profile->update(['profile_photo' => null, 'avatar_disk' => 'local']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Avatar removed.']);

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
