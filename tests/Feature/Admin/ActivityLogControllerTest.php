<?php

use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeActivityLogUser(string $role): User
{
    $user = User::factory()->create();
    UserProfile::factory()->{$role}()->create(['user_id' => $user->id]);

    return $user;
}

it('redirects guests from activity logs index', function () {
    $this->get(route('admin.activity-logs.index'))
        ->assertRedirect(route('login'));
});

it('denies students access to activity logs', function () {
    $student = makeActivityLogUser('student');

    $this->actingAs($student)
        ->get(route('admin.activity-logs.index'))
        ->assertForbidden();
});

it('allows admin to view activity logs index', function () {
    $admin = makeActivityLogUser('admin');

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ActivityLog/Index')
            ->has('logs.data')
            ->has('logs.links')
        );
});

it('allows staff to view activity logs index', function () {
    $staff = makeActivityLogUser('staff');

    $this->actingAs($staff)
        ->get(route('admin.activity-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ActivityLog/Index')
            ->has('logs.data')
            ->has('logs.links')
        );
});

it('includes logs without a subject for the frontend', function () {
    $admin = makeActivityLogUser('admin');

    activity()
        ->causedBy($admin)
        ->event('backed_up')
        ->log('Created backup test.zip');

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ActivityLog/Index')
            ->where(
                'logs.data',
                fn ($logs) => collect($logs)->contains(
                    fn (array $log): bool => $log['event'] === 'backed_up'
                        && $log['description'] === 'Created backup test.zip'
                        && $log['subject_type'] === null
                        && $log['subject_id'] === null
                        && $log['subject_name'] === null
                )
            )
        );
});

it('filters logs by causer ulid', function () {
    $admin = makeActivityLogUser('admin');
    $other = makeActivityLogUser('staff');

    activity()->causedBy($admin)->event('updated')->log('Admin action');
    activity()->causedBy($other)->event('updated')->log('Other action');

    $this->actingAs($admin)
        ->get(route('admin.activity-logs.index', ['causer' => $admin->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ActivityLog/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.causer', $admin->email)
            ->where('logs.data.0.description', 'Admin action')
        );
});
