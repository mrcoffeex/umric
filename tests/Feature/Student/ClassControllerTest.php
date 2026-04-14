<?php

use App\Models\SchoolClass;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

function classTestStudent(): User
{
    $user = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $user->id]);

    return $user;
}

it('shows student classes index with enrolled classes', function () {
    $student = classTestStudent();

    $class = SchoolClass::factory()->create([
        'name' => 'BSCS 4A',
        'section' => 'A',
    ]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Classes/Index')
            ->has('classes', 1)
            ->where('classes.0.name', 'BSCS 4A')
        );
});

it('shows empty classes list when student has no classes', function () {
    $student = classTestStudent();

    $this->actingAs($student)
        ->get(route('student.classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Classes/Index')
            ->has('classes', 0)
        );
});

it('denies access to non-student users', function () {
    $user = User::factory()->create();
    UserProfile::factory()->create(['user_id' => $user->id, 'role' => 'faculty']);

    $this->actingAs($user)
        ->get(route('student.classes.index'))
        ->assertForbidden();
});
