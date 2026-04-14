<?php

use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function makeSubjectAdminUser(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->admin()->create(['user_id' => $user->id]);

    return $user;
}

function makeSubjectProgram(): Program
{
    $department = Department::create([
        'name' => 'College of Computing',
        'code' => 'CC-'.fake()->unique()->numerify('###'),
        'description' => 'Department for computing programs',
        'is_active' => true,
    ]);

    return Program::create([
        'department_id' => $department->id,
        'name' => 'BS Information Technology '.fake()->unique()->numerify('###'),
        'code' => 'BSIT-'.fake()->unique()->numerify('###'),
        'description' => 'Program for IT students',
        'is_active' => true,
    ]);
}

it('redirects guests from subjects index', function () {
    $this->get(route('admin.subjects.index'))
        ->assertRedirect(route('login'));
});

it('denies non-admin users access to subjects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.subjects.index'))
        ->assertForbidden();
});

it('allows admin to view subjects index', function () {
    $admin = makeSubjectAdminUser();

    $this->actingAs($admin)
        ->get(route('admin.subjects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Subjects/Index')
            ->has('subjects')
            ->has('programs')
        );
});

it('allows admin to create a subject', function () {
    $admin = makeSubjectAdminUser();
    $program = makeSubjectProgram();

    $this->actingAs($admin)
        ->post(route('admin.subjects.store'), [
            'name' => 'Database Systems',
            'code' => 'CS101',
            'program_id' => $program->id,
            'year_level' => 2,
            'description' => 'Introduction to database design',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('subjects', [
        'name' => 'Database Systems',
        'code' => 'CS101',
        'program_id' => $program->id,
        'year_level' => 2,
    ]);
});

it('validates required fields for subject creation', function () {
    $admin = makeSubjectAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.subjects.store'), [])
        ->assertSessionHasErrors(['name', 'code']);
});

it('validates year_level is between 1 and 5 for subjects', function () {
    $admin = makeSubjectAdminUser();
    $program = makeSubjectProgram();

    $this->actingAs($admin)
        ->post(route('admin.subjects.store'), [
            'name' => 'Test',
            'code' => 'TST',
            'program_id' => $program->id,
            'year_level' => 0,
        ])
        ->assertSessionHasErrors(['year_level']);

    $this->actingAs($admin)
        ->post(route('admin.subjects.store'), [
            'name' => 'Test',
            'code' => 'TST',
            'program_id' => $program->id,
            'year_level' => 6,
        ])
        ->assertSessionHasErrors(['year_level']);
});

it('validates unique code when creating subject', function () {
    $admin = makeSubjectAdminUser();

    Subject::create([
        'name' => 'Existing Subject',
        'code' => 'SUBJ100',
        'program_id' => null,
        'description' => 'Existing description',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.subjects.store'), [
            'name' => 'Another Subject',
            'code' => 'SUBJ100',
        ])
        ->assertSessionHasErrors(['code']);
});

it('allows admin to update a subject', function () {
    $admin = makeSubjectAdminUser();

    $subject = Subject::create([
        'name' => 'Old Subject Name',
        'code' => 'SUBJ200',
        'program_id' => null,
        'description' => 'Old description',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.subjects.update', $subject), [
            'name' => 'New Subject Name',
            'code' => 'SUBJ200',
            'program_id' => null,
            'year_level' => 3,
            'description' => 'Updated description',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('subjects', [
        'id' => $subject->id,
        'name' => 'New Subject Name',
    ]);
});

it('allows admin to delete a subject', function () {
    $admin = makeSubjectAdminUser();

    $subject = Subject::create([
        'name' => 'Subject To Delete',
        'code' => 'SUBJ300',
        'program_id' => null,
        'description' => 'Delete this subject',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.subjects.destroy', $subject))
        ->assertRedirect();

    $this->assertDatabaseMissing('subjects', [
        'id' => $subject->id,
    ]);
});
