<?php

use App\Models\Sdg;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeSdgAdminUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

it('redirects guests from sdgs index', function () {
    $this->get(route('admin.sdgs.index'))
        ->assertRedirect(route('login'));
});

it('denies non-admin users access to sdgs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.sdgs.index'))
        ->assertForbidden();
});

it('allows admin to view sdgs index', function () {
    $admin = makeSdgAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.sdgs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Sdgs/Index')
            ->has('sdgs')
        );
});

it('allows admin to create an sdg', function () {
    $admin = makeSdgAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.sdgs.store'), [
            'number' => 1,
            'name' => 'No Poverty',
            'code' => 'SDG-01',
            'description' => 'End poverty in all its forms everywhere.',
            'color' => '#E5243B',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('sdgs', [
        'number' => 1,
        'code' => 'SDG-01',
        'name' => 'No Poverty',
    ]);
});

it('validates required fields for sdg creation', function () {
    $admin = makeSdgAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.sdgs.store'), [])
        ->assertSessionHasErrors(['number', 'name', 'code']);
});

it('validates unique number and code for sdg creation', function () {
    $admin = makeSdgAdminUser();

    Sdg::create([
        'number' => 1,
        'name' => 'Existing SDG',
        'code' => 'SDG-01',
        'description' => null,
        'color' => '#E5243B',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.sdgs.store'), [
            'number' => 1,
            'name' => 'Another SDG',
            'code' => 'SDG-01',
        ])
        ->assertSessionHasErrors(['number', 'code']);
});

it('allows admin to update an sdg', function () {
    $admin = makeSdgAdminUser();

    $sdg = Sdg::create([
        'number' => 2,
        'name' => 'Old SDG',
        'code' => 'SDG-02',
        'description' => 'Old description',
        'color' => '#DDA63A',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.sdgs.update', $sdg), [
            'number' => 2,
            'name' => 'Updated SDG',
            'code' => 'SDG-02',
            'description' => 'Updated description',
            'color' => '#000000',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('sdgs', [
        'id' => $sdg->id,
        'name' => 'Updated SDG',
    ]);
});

it('allows admin to delete an sdg', function () {
    $admin = makeSdgAdminUser();

    $sdg = Sdg::create([
        'number' => 3,
        'name' => 'Delete SDG',
        'code' => 'SDG-03',
        'description' => 'Delete me',
        'color' => '#4C9F38',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.sdgs.destroy', $sdg))
        ->assertRedirect();

    $this->assertDatabaseMissing('sdgs', [
        'id' => $sdg->id,
    ]);
});
