<?php

use App\Models\Department;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeClassAdminUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

function makeClassProgram(): Program
{
    $department = Department::create([
        'name' => 'College of Computing',
        'code' => 'CC-'.fake()->unique()->numerify('###'),
        'description' => 'Department for computing programs',
        'is_active' => true,
    ]);

    return Program::create([
        'department_id' => $department->id,
        'name' => 'BS Computer Science '.fake()->unique()->numerify('###'),
        'code' => 'BSCS-'.fake()->unique()->numerify('###'),
        'description' => 'Program for CS students',
        'is_active' => true,
    ]);
}

it('redirects guests from classes index', function () {
    $this->get(route('admin.classes.index'))
        ->assertRedirect(route('login'));
});

it('denies non-admin users access to classes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.classes.index'))
        ->assertForbidden();
});

it('allows admin to view classes index', function () {
    $admin = makeClassAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Classes/Index')
            ->has('classes')
            ->has('programs')
        );
});

it('allows admin to create a class', function () {
    $admin = makeClassAdminUser();
    $program = makeClassProgram();

    $this->actingAs($admin)
        ->post(route('admin.classes.store'), [
            'program_id' => $program->id,
            'name' => 'Class 1-A',
            'school_year' => '2025-2026',
            'semester' => 1,
            'year_level' => 1,
            'section' => 'A',
            'description' => 'First year class',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('school_classes', [
        'program_id' => $program->id,
        'name' => 'Class 1-A',
        'year_level' => 1,
        'section' => 'A',
    ]);
});

it('validates required fields for class creation', function () {
    $admin = makeClassAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.classes.store'), [])
        ->assertSessionHasErrors(['program_id', 'name', 'year_level', 'section']);
});

it('validates program_id exists for class creation', function () {
    $admin = makeClassAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.classes.store'), [
            'program_id' => 99999,
            'name' => 'Invalid Program Class',
            'year_level' => 2,
            'section' => 'B',
        ])
        ->assertSessionHasErrors(['program_id']);
});

it('allows admin to update a class', function () {
    $admin = makeClassAdminUser();
    $program = makeClassProgram();

    $class = SchoolClass::create([
        'program_id' => $program->id,
        'name' => 'Old Class Name',
        'year_level' => 2,
        'section' => 'B',
        'description' => 'Old class description',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.classes.update', $class), [
            'program_id' => $program->id,
            'name' => 'New Class Name',
            'year_level' => 2,
            'section' => 'B',
            'description' => 'Updated class description',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('school_classes', [
        'id' => $class->id,
        'name' => 'New Class Name',
    ]);
});

it('allows admin to delete a class', function () {
    $admin = makeClassAdminUser();
    $program = makeClassProgram();

    $class = SchoolClass::create([
        'program_id' => $program->id,
        'name' => 'Class To Delete',
        'year_level' => 3,
        'section' => 'C',
        'description' => 'Delete this class',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.classes.destroy', $class))
        ->assertRedirect();

    $this->assertDatabaseMissing('school_classes', [
        'id' => $class->id,
    ]);
});
