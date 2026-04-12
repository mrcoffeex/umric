<?php

use App\Models\Agenda;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeAgendaAdminUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

it('redirects guests from agendas index', function () {
    $this->get(route('admin.agendas.index'))
        ->assertRedirect(route('login'));
});

it('denies non-admin users access to agendas', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.agendas.index'))
        ->assertForbidden();
});

it('allows admin to view agendas index', function () {
    $admin = makeAgendaAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.agendas.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Agendas/Index')
            ->has('agendas')
        );
});

it('allows admin to create an agenda', function () {
    $admin = makeAgendaAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.agendas.store'), [
            'name' => 'Science, Technology and Innovation',
            'code' => 'AGD009',
            'description' => 'Studies advancing applied science and engineering.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('agendas', [
        'code' => 'AGD009',
        'name' => 'Science, Technology and Innovation',
    ]);
});

it('validates required fields for agenda creation', function () {
    $admin = makeAgendaAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.agendas.store'), [])
        ->assertSessionHasErrors(['name', 'code']);
});

it('validates unique code when creating agenda', function () {
    $admin = makeAgendaAdminUser();

    Agenda::create([
        'name' => 'Existing Agenda',
        'code' => 'AGD001',
        'description' => null,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.agendas.store'), [
            'name' => 'Another Agenda',
            'code' => 'AGD001',
        ])
        ->assertSessionHasErrors(['code']);
});

it('allows admin to update an agenda', function () {
    $admin = makeAgendaAdminUser();

    $agenda = Agenda::create([
        'name' => 'Old Agenda',
        'code' => 'AGD010',
        'description' => 'Old description',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.agendas.update', $agenda), [
            'name' => 'Updated Agenda',
            'code' => 'AGD010',
            'description' => 'Updated description',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('agendas', [
        'id' => $agenda->id,
        'name' => 'Updated Agenda',
    ]);
});

it('allows admin to delete an agenda', function () {
    $admin = makeAgendaAdminUser();

    $agenda = Agenda::create([
        'name' => 'Delete Agenda',
        'code' => 'AGD011',
        'description' => 'Delete me',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.agendas.destroy', $agenda))
        ->assertRedirect();

    $this->assertDatabaseMissing('agendas', [
        'id' => $agenda->id,
    ]);
});
