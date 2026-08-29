<?php

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MaintenanceService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeMaintenanceUser(string $role): User
{
    $user = User::factory()->create();
    UserProfile::factory()->{$role}()->create(['user_id' => $user->id]);

    return $user;
}

function enableMaintenance(?string $message = null): void
{
    app(MaintenanceService::class)->update([
        'maintenance_mode' => true,
        'maintenance_message' => $message,
    ]);
}

it('redirects guests from maintenance settings', function () {
    $this->get(route('admin.maintenance.index'))
        ->assertRedirect(route('login'));
});

it('denies staff from maintenance settings', function () {
    $staff = makeMaintenanceUser('staff');

    $this->actingAs($staff)
        ->get(route('admin.maintenance.index'))
        ->assertForbidden();
});

it('allows admin to view and update maintenance settings', function () {
    $admin = makeMaintenanceUser('admin');

    $this->actingAs($admin)
        ->get(route('admin.maintenance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Maintenance/Index')
            ->where('maintenance.enabled', false)
        );

    $this->actingAs($admin)
        ->put(route('admin.maintenance.update'), [
            'enabled' => true,
            'message' => 'Scheduled upgrades in progress.',
        ])
        ->assertRedirect(route('admin.maintenance.index'));

    $record = SystemSetting::query()->first();
    expect($record)->not->toBeNull();
    expect($record->maintenance_mode)->toBeTrue();
    expect($record->maintenance_message)->toBe('Scheduled upgrades in progress.');
});

it('blocks non-admin roles with a flash toast during maintenance', function (string $role) {
    enableMaintenance('Back soon.');

    $user = makeMaintenanceUser($role);

    $target = $role === 'student' ? route('student.home') : route('dashboard');

    $this->actingAs($user)
        ->get($target)
        ->assertRedirect(route('maintenance'));

    $this->actingAs($user)
        ->get(route('maintenance'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Maintenance')
            ->where('message', 'Back soon.')
        );
})->with(['staff', 'faculty', 'student']);

it('allows admins to use the system during maintenance', function () {
    enableMaintenance();

    $admin = makeMaintenanceUser('admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.maintenance.index'))
        ->assertOk();
});

it('redirects away from the maintenance page when mode is off', function () {
    $student = makeMaintenanceUser('student');

    $this->actingAs($student)
        ->get(route('maintenance'))
        ->assertRedirect(route('dashboard'));
});

it('disables maintenance mode for all roles', function () {
    enableMaintenance();

    $admin = makeMaintenanceUser('admin');
    $student = makeMaintenanceUser('student');

    $this->actingAs($admin)
        ->put(route('admin.maintenance.update'), [
            'enabled' => false,
            'message' => null,
        ])
        ->assertRedirect(route('admin.maintenance.index'));

    $this->actingAs($student)
        ->get(route('student.home'))
        ->assertOk();
});
