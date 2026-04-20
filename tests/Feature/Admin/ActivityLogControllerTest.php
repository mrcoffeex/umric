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
