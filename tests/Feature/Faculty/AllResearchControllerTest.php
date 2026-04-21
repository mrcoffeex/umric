<?php

use App\Models\Agenda;
use App\Models\ResearchPaper;
use App\Models\SchoolClass;
use App\Models\Sdg;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('shows all research papers from faculty classes', function () {
    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $class = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'current_step' => 'title_proposal',
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/Research/AllIndex')
            ->has('papers.data', 1)
            ->has('classes', 1)
            ->has('stepCounts')
            ->has('stepLabels')
        );
});

it('does not include papers from other faculty classes', function () {
    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $otherFaculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $otherFaculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $ownClass = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);
    $otherClass = SchoolClass::factory()->create(['faculty_id' => $otherFaculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $ownClass->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $otherStudent = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $otherStudent->id]);
    DB::table('school_class_members')->insert([
        'school_class_id' => $otherClass->id,
        'student_id' => $otherStudent->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create(['user_id' => $student->id]);
    ResearchPaper::factory()->create(['user_id' => $otherStudent->id]);

    $this->actingAs($faculty)
        ->get(route('faculty.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('papers.data', 1)
        );
});

it('includes papers where faculty is assigned as adviser or statistician', function () {
    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $classOwner = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $classOwner->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $class = SchoolClass::factory()->create(['faculty_id' => $classOwner->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $class->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'adviser_id' => $faculty->id,
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $class->id,
        'statistician_id' => $faculty->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('faculty/Research/AllIndex')
            ->has('papers.data', 2)
        );
});

it('uses the student membership class for class column', function () {
    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    $student = User::factory()->create();
    UserProfile::factory()->student()->create(['user_id' => $student->id]);

    $memberClass = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);
    $paperClass = SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    DB::table('school_class_members')->insert([
        'school_class_id' => $memberClass->id,
        'student_id' => $student->id,
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ResearchPaper::factory()->create([
        'user_id' => $student->id,
        'school_class_id' => $paperClass->id,
    ]);

    $this->actingAs($faculty)
        ->get(route('faculty.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('papers.data.0.school_class.id', $memberClass->id)
            ->where('papers.data.0.school_class.name', $memberClass->name)
        );
});

it('redirects guests away from faculty research index', function () {
    $this->get(route('faculty.research.index'))
        ->assertRedirect();
});

it('passes sdgs and agendas to the all research index', function () {
    $faculty = User::factory()->create();
    UserProfile::factory()->faculty()->create(['user_id' => $faculty->id]);

    SchoolClass::factory()->create(['faculty_id' => $faculty->id]);

    Sdg::factory()->create(['is_active' => true]);
    Agenda::factory()->create(['is_active' => true]);

    $this->actingAs($faculty)
        ->get(route('faculty.research.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('sdgs', 1)
            ->has('agendas', 1)
        );
});
