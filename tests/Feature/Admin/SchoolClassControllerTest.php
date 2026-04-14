<?php

use App\Models\SchoolClass;
use App\Models\Subject;
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
            ->has('facultyUsers')
            ->has('subjects')
        );
});

it('allows admin to create a class', function () {
    $admin = makeClassAdminUser();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.classes.store'), [
            'subject_id' => $subject->id,
            'name' => 'Class 1-A',
            'school_year' => '2025-2026',
            'semester' => 1,
            'section' => 'A',
            'description' => 'First year class',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('school_classes', [
        'name' => 'Class 1-A',
        'section' => 'A',
    ]);

    $createdClass = SchoolClass::query()->where('name', 'Class 1-A')->firstOrFail();

    $this->assertDatabaseHas('school_class_subjects', [
        'school_class_id' => $createdClass->id,
        'subject_id' => $subject->id,
    ]);
});

it('validates required fields for class creation', function () {
    $admin = makeClassAdminUser();

    $this->actingAs($admin)
        ->post(route('admin.classes.store'), [])
        ->assertSessionHasErrors(['subject_id', 'name', 'section']);
});

it('validates faculty_id exists for class creation', function () {
    $admin = makeClassAdminUser();
    $subject = Subject::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.classes.store'), [
            'faculty_id' => 99999,
            'subject_id' => $subject->id,
            'name' => 'Invalid Program Class',
            'section' => 'B',
        ])
        ->assertSessionHasErrors(['faculty_id']);
});

it('allows admin to update a class', function () {
    $admin = makeClassAdminUser();
    $subject = Subject::factory()->create();

    $class = SchoolClass::create([
        'name' => 'Old Class Name',
        'section' => 'B',
        'description' => 'Old class description',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.classes.update', $class), [
            'subject_id' => $subject->id,
            'name' => 'New Class Name',
            'section' => 'B',
            'description' => 'Updated class description',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('school_classes', [
        'id' => $class->id,
        'name' => 'New Class Name',
    ]);

    $this->assertDatabaseHas('school_class_subjects', [
        'school_class_id' => $class->id,
        'subject_id' => $subject->id,
    ]);
});

it('allows admin to delete a class', function () {
    $admin = makeClassAdminUser();

    $class = SchoolClass::create([
        'name' => 'Class To Delete',
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
